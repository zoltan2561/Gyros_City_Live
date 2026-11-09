(function(root,factory){const api=factory();if(typeof module==='object'&&module.exports)module.exports=api;else root.GCSignageSchedule=api;}(globalThis,function(){
'use strict';
const formatter=new Intl.DateTimeFormat('en-GB',{timeZone:'Europe/Budapest',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'});
function localParts(now){const p=Object.fromEntries(formatter.formatToParts(now).map(p=>[p.type,p.value]));const date=p.year+'-'+p.month+'-'+p.day;return {date,time:p.hour+':'+p.minute,day:new Date(date+'T12:00:00Z').getUTCDay()||7};}
function selectRelease(manifest,now){
 if(!manifest||now.getTime()>=Date.parse(manifest.schedule_valid_until))return null;
 const p=localParts(now),prior=new Date(p.date+'T12:00:00Z');prior.setUTCDate(prior.getUTCDate()-1);
 const previous=prior.toISOString().slice(0,10),previousDay=prior.getUTCDay()||7;
 for(const r of [...manifest.schedules].sort((a,b)=>b.priority-a.priority)){
  const overnight=r.end_time<=r.start_time;
  let date=p.date,day=p.day,active=p.time>=r.start_time&&(overnight||p.time<r.end_time);
  if(overnight&&p.time<r.end_time){date=previous;day=previousDay;active=true;}
  if(active&&date>=r.starts_on&&date<=r.ends_on&&r.days.map(Number).includes(day))return Number(r.release_id);
 }
 return manifest.default_release_id;
}
function sceneAllowed(scene,state){
 const media=[scene.media,scene.background_media].filter(Boolean);
 if(media.some(m=>(state.invalid_media||[]).includes(m.checksum)))return false;
 if(!state.fresh&&(scene.type!=='media'||media.some(m=>m.baked_prices)))return false;
 return true;
}
return {selectRelease,sceneAllowed};
}));
