
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'playwright';

const root=process.cwd();
const php=process.env.PHP_BINARY||(fs.existsSync(path.join(root,'.tools/php/php.exe'))?path.join(root,'.tools/php/php.exe'):'php');
const database=path.join(root,'artifacts/browser.sqlite');
const base='http://127.0.0.1:8765';
const environment={...process.env,APP_ENV:'testing',APP_NAME:'ScholarMatch',APP_DEBUG:'true',APP_URL:base,DB_CONNECTION:'sqlite',DB_DATABASE:database,SESSION_DRIVER:'database',SESSION_ENCRYPT:'true',CACHE_STORE:'file',BCRYPT_ROUNDS:'4'};
let server,browser,serverOutput='';
function artisan(...args){
 const run=spawnSync(php,['artisan',...args],{cwd:root,env:environment,encoding:'utf8'});
 assert.equal(run.status,0,run.stdout+run.stderr);
}
before(async()=>{
 fs.mkdirSync(path.join(root,'artifacts'),{recursive:true});fs.writeFileSync(database,'');
 artisan('migrate:fresh','--seed','--force','--no-interaction');
 server=spawn(php,['-S','127.0.0.1:8765',path.join(root,'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],{cwd:path.join(root,'public'),env:environment,windowsHide:true,stdio:['ignore','pipe','pipe']});
 server.stdout.on('data',d=>serverOutput+=d);server.stderr.on('data',d=>serverOutput+=d);
 const started=Date.now();
 while(true){
  try{const r=await fetch(base+'/up');if(r.ok)break;}catch{}
  assert.ok(Date.now()-started<20000,serverOutput);
  await new Promise(resolve=>setTimeout(resolve,100));
 }
 browser=await chromium.launch({headless:true});
});
after(async()=>{await browser?.close();server?.kill();fs.writeFileSync(path.join(root,'artifacts/browser-server.txt'),serverOutput);});
async function pageFor(t,email='student1@scholarmatch.test',viewport={width:1440,height:1000}){
 const context=await browser.newContext({viewport});const page=await context.newPage();const errors=[];
 page.on('pageerror',e=>errors.push(e.message));
 page.on('console',m=>{if(['error','warning'].includes(m.type()))errors.push(m.text());});
 t.after(async()=>{assert.deepEqual(errors,[],'Browser must have no JavaScript or console errors');await context.close();});
 await page.goto(base+'/login');await page.getByLabel('Email address').fill(email);await page.getByLabel('Password',{exact:true}).fill('Demo!123456');
 await Promise.all([page.waitForURL('**/dashboard'),page.getByRole('button',{name:'Sign in'}).click()]);
 return page;
}
test('desktop dashboard, recommendation filters, details and bookmark round trip',async t=>{
 const page=await pageFor(t);assert.ok(await page.getByRole('heading',{name:'Selamat datang, Aina!',exact:false}).isVisible());
 await page.screenshot({path:'artifacts/dashboard-desktop.png',fullPage:true});
 await page.getByRole('link',{name:'Recommendations',exact:true}).click();await page.getByLabel('Eligibility',{exact:true}).selectOption('Eligible');
 await Promise.all([page.waitForURL('**/recommendations?**'),page.getByRole('button',{name:'Apply filters'}).click()]);
 const card=page.locator('.scholarship-card').first();assert.match(await card.innerText(),/26\.7% above the academic minimum/);
 await page.screenshot({path:'artifacts/recommendations-desktop.png',fullPage:true});
 await card.getByRole('link',{name:'View Details'}).click();assert.ok(await page.getByRole('heading',{name:'Requirement breakdown'}).isVisible());
 assert.equal(await page.getByRole('link',{name:'Apply on Official Website'}).getAttribute('href'),'https://example.org/demo-application');
 await page.screenshot({path:'artifacts/details-desktop.png',fullPage:true});
 await page.getByRole('button',{name:/Save Demo/}).click();
 await page.getByRole('link',{name:'Saved Scholarships',exact:true}).click();assert.equal(await page.locator('.scholarship-card').count(),1);
 await page.getByRole('button',{name:/Remove saved/}).click();assert.ok(await page.getByRole('heading',{name:'A little space for your next chapter'}).isVisible());
});
test('add and remove qualification controls persist a validated profile',async t=>{
 const page=await pageFor(t);await page.goto(base+'/profile');const original=await page.locator('.academic-row').count();
 await page.getByRole('button',{name:'Add Qualification'}).click();const added=page.locator('.academic-row').last();
 await added.getByLabel('Qualification',{exact:true}).selectOption('Foundation');await added.getByLabel('Completion status').selectOption('completed');
 await added.getByLabel('Result type').selectOption('cgpa');await added.getByLabel('Result value',{exact:false}).fill('3.75');await added.getByLabel('Grading scale maximum').fill('4');
 await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Save profile'}).click()]);
 assert.equal(await page.locator('.academic-row').count(),original+1);assert.ok(await page.getByRole('status').isVisible());
 await page.locator('.academic-row').last().getByRole('button',{name:'Remove qualification'}).click();
 await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Save profile'}).click()]);
 assert.equal(await page.locator('.academic-row').count(),original);
});
test('mobile menu works and principal student pages do not overflow',async t=>{
 const page=await pageFor(t,'student1@scholarmatch.test',{width:390,height:844});
 assert.equal(await page.locator('.sidebar').isVisible(),false);await page.getByRole('button',{name:'Toggle navigation'}).click();
 assert.equal(await page.getByRole('button',{name:'Toggle navigation'}).getAttribute('aria-expanded'),'true');
 assert.ok(await page.getByRole('link',{name:'My Profile',exact:true}).isVisible());await page.getByRole('button',{name:'Toggle navigation'}).click();
 for(const route of ['/dashboard','/recommendations','/profile','/saved','/scholarships/1']){
  await page.goto(base+route);assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth),route+' must fit a phone viewport');
 }
 await page.goto(base+'/dashboard');await page.screenshot({path:'artifacts/dashboard-mobile.png',fullPage:true});
});
test('admin scholarship route controls, reference rows and validation are usable',async t=>{
 const page=await pageFor(t,'admin@scholarmatch.test');
 await page.getByRole('link',{name:'Scholarships',exact:true}).click();await page.getByRole('link',{name:'Add Scholarship'}).click();
 await page.getByRole('button',{name:'Add academic route'}).click();assert.equal(await page.locator('.route-row').count(),1);
 await page.locator('.route-row').getByRole('button',{name:'Remove route'}).click();assert.equal(await page.locator('.route-row').count(),0);
 await page.getByRole('link',{name:'Income Categories',exact:true}).click();const initial=await page.locator('.income-row').count();
 await page.getByRole('button',{name:'Add category'}).click();assert.equal(await page.locator('.income-row').count(),initial+1);
 await page.locator('.income-row').last().getByRole('button',{name:'Remove',exact:true}).click();assert.equal(await page.locator('.income-row').count(),initial);
 await page.getByRole('link',{name:'Study Fields',exact:true}).click();await page.getByLabel('New field name').fill('Demo Browser Field');
 await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Add field',exact:true}).click()]);assert.match(await page.getByRole('status').innerText(),/Study field added/);
});
test('academic pathways are clear and fit desktop and small phone cards',async t=>{
 const page=await pageFor(t);await page.goto(base+'/recommendations');
 const card=page.locator('.scholarship-card').first();
 const requirements=card.getByRole('group',{name:'Academic requirements'});
 assert.equal(await requirements.locator('.academic-pathway').count(),2);
 assert.match(await requirements.innerText(),/SPM\s+At least 8 As\s+Completed qualification\s+OR\s+Diploma\s+CGPA of at least 3\.00\/4\.00/);
 assert.match(await requirements.innerText(),/Meet any one pathway/);
 assert.doesNotMatch(await requirements.innerText(),/\(scale |Number of As ≥/);
 for(const width of [1440,390,320]){
  await page.setViewportSize({width,height:1000});
  assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth),`Cards must fit ${width}px`);
  assert.ok(await requirements.locator('.academic-pathway').evaluateAll(rows=>rows.every(row=>{
   const badge=row.querySelector('.pathway-qualification').getBoundingClientRect();
   const description=row.querySelector('.pathway-description');
   return badge.right<=description.getBoundingClientRect().left&&description.scrollWidth<=description.clientWidth;
  })),`Pathway text must not overlap or overflow at ${width}px`);
  if(width!==320)await card.screenshot({path:`artifacts/academic-card-${width===1440?'desktop':'mobile'}.png`});
 }
});
test('registration assigns a student account and logout returns to sign in',async t=>{
 const context=await browser.newContext();const page=await context.newPage();t.after(()=>context.close());
 await page.goto(base+'/register');await page.getByLabel('Full name').fill('Browser Student');await page.getByLabel('Email address').fill('browser@scholarmatch.test');
 await page.getByLabel('Password',{exact:true}).fill('Browser!12345');await page.getByLabel('Confirm password').fill('Browser!12345');
 await Promise.all([page.waitForURL('**/dashboard'),page.getByRole('button',{name:'Create account'}).click()]);
 assert.ok(await page.getByText('Student account',{exact:true}).isVisible());
 await Promise.all([page.waitForURL('**/login'),page.getByRole('button',{name:'Logout',exact:false}).click()]);
 assert.ok(await page.getByRole('heading',{name:'Welcome back'}).isVisible());
});
