var S=Object.defineProperty;var y=Object.getOwnPropertySymbols;var V=Object.prototype.hasOwnProperty,$=Object.prototype.propertyIsEnumerable;var T=(t,l,s)=>l in t?S(t,l,{enumerable:!0,configurable:!0,writable:!0,value:s}):t[l]=s,k=(t,l)=>{for(var s in l||(l={}))V.call(l,s)&&T(t,s,l[s]);if(y)for(var s of y(l))$.call(l,s)&&T(t,s,l[s]);return t};import{u as j,r as A,o as B,t as L,e as i,y as N,f as n,g as _,k as a,w as e,z as w,F as f,l as p,j as c,q as m,A as D}from"./vendor.51c5b88d.js";import{_ as C,a as z}from"./index.c2d00f8c.js";const E={name:"Dashboard",emits:["updateVersion"],setup(t,l){j();const s=A({statData:{loading:!0,user:{},torrent:{},user_class:{},system_info:{}},latestUser:{loading:!0,data:[]},latestTorrent:{loading:!0,data:[]}});return B(()=>{z.listStatData().then(d=>{s.statData=d.data,s.statData.loading=!1,l.emit("updateVersion",d.data.system_info.data)}),z.listLatestUser().then(d=>{s.latestUser.data=d.data,s.latestUser.loading=!1}),z.listLatestTorrent().then(d=>{s.latestTorrent.data=d.data,s.latestTorrent.loading=!1})}),k({},L(s))}};function F(t,l,s,d,R,q){const o=i("el-table-column"),U=i("el-table"),x=i("el-card"),u=i("el-col"),v=i("el-row"),b=i("el-descriptions-item"),g=i("el-descriptions"),h=N("loading");return n(),_(f,null,[a(v,null,{default:e(()=>[a(u,{span:12,class:"stat-box"},{default:e(()=>[a(x,null,{header:e(()=>[p(c(t.latestUser.data.page_title),1)]),default:e(()=>[w((n(),m(U,{data:t.latestUser.data.data,size:"mini"},{default:e(()=>[a(o,{prop:"username",label:"Username"}),a(o,{prop:"email",label:"Email"}),a(o,{prop:"status",label:"Status"}),a(o,{prop:"added",label:"Added",width:"180"})]),_:1},8,["data"])),[[h,t.latestUser.loading]])]),_:1})]),_:1}),a(u,{span:12,class:"stat-box"},{default:e(()=>[a(x,null,{header:e(()=>[p(c(t.latestTorrent.data.page_title),1)]),default:e(()=>[w((n(),m(U,{data:t.latestTorrent.data.data,size:"mini"},{default:e(()=>[a(o,{prop:"name",label:"Name"}),a(o,{prop:"user.username",label:"User",width:"150"}),a(o,{prop:"size_human",label:"Size",width:"100"}),a(o,{prop:"added",label:"Added",width:"180"})]),_:1},8,["data"])),[[h,t.latestTorrent.loading]])]),_:1})]),_:1})]),_:1}),w((n(),_("div",null,[a(v,{class:"row"},{default:e(()=>[a(u,{span:12,class:"stat-box"},{default:e(()=>[a(g,{title:t.statData.user.text,column:2,size:"mini",border:""},{default:e(()=>[(n(!0),_(f,null,D(t.statData.user.data,r=>(n(),m(b,{label:r.text},{default:e(()=>[p(c(r.value),1)]),_:2},1032,["label"]))),256))]),_:1},8,["title"])]),_:1}),a(u,{span:12,class:"stat-box"},{default:e(()=>[a(g,{title:t.statData.user_class.text,column:2,size:"mini",border:""},{default:e(()=>[(n(!0),_(f,null,D(t.statData.user_class.data,r=>(n(),m(b,{label:r.class_text},{default:e(()=>[p(c(r.counts),1)]),_:2},1032,["label"]))),256))]),_:1},8,["title"])]),_:1})]),_:1}),a(v,{class:"row"},{default:e(()=>[a(u,{span:12,class:"stat-box"},{default:e(()=>[a(g,{title:t.statData.torrent.text,column:2,size:"mini",border:""},{default:e(()=>[(n(!0),_(f,null,D(t.statData.torrent.data,r=>(n(),m(b,{label:r.text},{default:e(()=>[p(c(r.value),1)]),_:2},1032,["label"]))),256))]),_:1},8,["title"])]),_:1}),a(u,{span:12,class:"stat-box"},{default:e(()=>[a(g,{title:t.statData.system_info.text,column:2,size:"mini",border:""},{default:e(()=>[(n(!0),_(f,null,D(t.statData.system_info.data,r=>(n(),m(b,{label:r.text},{default:e(()=>[p(c(r.value),1)]),_:2},1032,["label"]))),256))]),_:1},8,["title"])]),_:1})]),_:1})])),[[h,t.statData.loading]])],64)}var H=C(E,[["render",F],["__scopeId","data-v-64158476"]]);export{H as default};
