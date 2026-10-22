const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const flush = () => new Promise(resolve => setImmediate(resolve));

function setup(url='http://localhost/admin/orders') {
    const events={}, timers=new Map(), requests=[];
    const state={fail:false,modal:false,select:false,ids:['1','2'],next:['3','2'],rootUpdates:0,statsUpdates:0};
    const rows=new Map();
    function row(id) {const classes=new Set();return {id:'dataid'+id,offsetWidth:100,classList:{add:x=>classes.add(x),remove:x=>classes.delete(x)},classes};}
    state.ids.forEach(id=>rows.set(id,row(id)));
    const table={scrollLeft:55};
    const root={querySelectorAll:()=>[...rows.values()],querySelector:selector=>selector==='select:disabled'?null:table,
        replaceChildren(...children){state.rootUpdates++;rows.clear();children.forEach(r=>rows.set(r.id.slice(6),r));}};
    const span={textContent:''}, feedback={hidden:true,querySelector:()=>span};
    const stats={replaceChildren(){state.statsUpdates++;}};
    const document={currentScript:{dataset:{refreshUrl:url}},hidden:false,
        getElementById:id=>({'orders-live-results':root,'orders-statistics':stats,'orders-arrival-feedback':feedback}[id] || rows.get(id.slice(6))),
        querySelector:()=>state.modal?{}:null,activeElement:{matches:()=>state.select},
        addEventListener:(name,fn)=>{events[name]=fn;}};
    let timer=0;
    vm.runInNewContext(fs.readFileSync('public/city/admin-orders-live.js','utf8'),{
        document, URL, AbortController,
        setTimeout:(fn,ms)=>{timers.set(++timer,{fn,ms});return timer;},clearTimeout:id=>timers.delete(id),
        fetch:async (...args)=>{requests.push(args);if(state.fail)throw new Error('offline');return {ok:true,text:async()=>'<fragment>'};},
        DOMParser:class {parseFromString(){return {getElementById:id=>id==='orders-live-results'?{childNodes:state.next.map(row)}:{childNodes:[{}]}};}}
    });
    return {state,requests,rows,span,feedback,events,table,
        poll:async ids=>{events['admin:orders-poll']({detail:{ids}});await flush();},
        blur:async()=>{state.select=false;events.focusout();for(const [id,t] of [...timers])if(t.ms===0){timers.delete(id);t.fn();}await flush();}};
}
test('a new order refreshes rows and counters once, preserving horizontal scroll and highlighting its row',async()=>{
    const ui=setup();await ui.poll(['1']);await ui.poll(['1','3']);
    assert.equal(ui.requests.length,1);
    assert.equal(ui.requests[0][1].headers['X-Orders-Refresh'],'1');
    assert.equal(ui.state.rootUpdates,1);assert.equal(ui.state.statsUpdates,1);
    assert.ok(ui.rows.get('3').classes.has('order-row-new'));
    assert.equal(ui.table.scrollLeft,55);
    assert.equal(ui.feedback.hidden,false);assert.match(ui.span.textContent,/1 új rendelés/);
    await ui.poll(['1','3']);assert.equal(ui.requests.length,1);
});
test('an arrival between the initial HTML and first poll is fetched immediately',async()=>{
    const ui=setup();await ui.poll(['3']);assert.equal(ui.requests.length,1);
});
test('a failed refresh retries on the next successful order poll instead of losing the arrival',async()=>{
    const ui=setup();await ui.poll(['1']);ui.state.fail=true;await ui.poll(['1','3']);
    assert.equal(ui.state.rootUpdates,0);assert.match(ui.span.textContent,/nem sikerült/);
    ui.state.fail=false;await ui.poll(['1','3']);assert.equal(ui.state.rootUpdates,1);
});
test('an active status control defers replacement until focus leaves it',async()=>{
    const ui=setup();await ui.poll(['1']);ui.state.select=true;await ui.poll(['1','3']);
    assert.equal(ui.requests.length,0);await ui.blur();assert.equal(ui.requests.length,1);
});
test('filters and older pages stay on their current URL and do not flag old pending orders on startup',async()=>{
    const url='http://localhost/admin/orders?page=2&q=Customer&status=processing';
    const ui=setup(url);await ui.poll(['20']);assert.equal(ui.requests.length,0);
    await ui.poll(['20','21']);assert.equal(ui.requests[0][0],url);
});
test('a payment modal defers refresh until it closes',async()=>{
    const ui=setup();await ui.poll(['1']);ui.state.modal=true;await ui.poll(['1','3']);
    assert.equal(ui.requests.length,0);ui.state.modal=false;ui.events['hidden.bs.modal']();await flush();
    assert.equal(ui.requests.length,1);
});
