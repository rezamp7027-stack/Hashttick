const Hashttick=(()=> {
  const $=s=>document.querySelector(s);
  const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  const csrf=()=>document.querySelector('meta[name="csrf-token"]')?.content||'';
  const safeHttpUrl=value=>{try{const u=new URL(String(value||''),location.href);return ['http:','https:'].includes(u.protocol)?u.href:''}catch(_e){return ''}};

  let refreshPromise=null;

  function refreshSession(){
    if(refreshPromise)return refreshPromise;
    refreshPromise=(async()=>{
      try{
        const rr=await fetch('api/auth/refresh.php',{
          method:'POST',
          credentials:'same-origin',
          headers:{'X-CSRF-Token':csrf()}
        });
        const rd=await rr.json().catch(()=>({success:false}));
        return rr.ok&&rd.success?rd:null;
      }catch(_e){
        return null;
      }finally{
        refreshPromise=null;
      }
    })();
    return refreshPromise;
  }

  async function api(url,opt={},allowRefresh=true){
    const method=String(opt.method||'GET').toUpperCase();
    const headers=new Headers(opt.headers||{});
    if(method!=='GET'&&method!=='HEAD') headers.set('X-CSRF-Token',csrf());
    const request={credentials:'same-origin',...opt,headers};
    const r=await fetch(url,request);
    const d=await r.json().catch(()=>({success:false,error:'پاسخ نامعتبر از سرور'}));

    if(r.status===401&&allowRefresh&&!url.includes('api/auth/refresh.php')){
      const rd=await refreshSession();
      if(rd?.success)return api(url,opt,false);
      location.href=rd?.redirect||d.redirect||'login.php';
      throw new Error(rd?.error||'جلسه ورود منقضی شده است؛ دوباره وارد شوید');
    }

    if(r.status===401&&d.redirect){
      location.href=d.redirect;
      throw new Error(d.error||'لطفاً وارد شوید');
    }

    if(!r.ok||d.success===false)throw new Error(d.error||'خطا');
    return d;
  }

  const fd=f=>new URLSearchParams(new FormData(f));
  const post=(url,data)=>api(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:data instanceof URLSearchParams?data:new URLSearchParams(data)});

  function openModal(id){$('#'+id)?.classList.remove('hidden')}
  function closeModal(id){$('#'+id)?.classList.add('hidden')}
  function openWordCreate(){const f=$('#addWordForm');if(!f)return;f.reset();if(f.elements.id)f.elements.id.value='';$('#wordModalTitle').textContent='افزودن واژه';$('#wordCancelEdit')?.classList.add('hidden');openModal('wordModal')}

  function speak(text,audioUrl=''){
    const src=safeHttpUrl(audioUrl);
    if(src){
      try{
        const audio=new Audio(src);
        audio.preload='auto';
        audio.play().catch(()=>speakFallback(text));
        return true;
      }catch(_e){}
    }
    return speakFallback(text);
  }
  function speakFallback(text){
    if(!text||!('speechSynthesis'in window))return false;
    speechSynthesis.cancel();
    const u=new SpeechSynthesisUtterance(text);
    u.lang='en-US';
    speechSynthesis.speak(u);
    return true;
  }

  async function auth(id,url,redirect){
    const f=$('#'+id);
    f?.addEventListener('submit',async e=>{
      e.preventDefault();
      const err=f.querySelector('.form-error');
      err.textContent='';
      const b=f.querySelector('button[type="submit"],button:not([type])');
      if(b)b.disabled=true;
      try{
        const d=await post(url,fd(f));
        if(d.requiresEmailConfirmation){
          err.style.color='var(--green)';
          err.textContent=d.message;
          return;
        }
        location.href=redirect;
      }catch(x){err.textContent=x.message}
      finally{if(b)b.disabled=false}
    });
  }

  async function dashboard(){
    try{
      const d=await api('api/dashboard.php');
      $('#activeWords').textContent=d.activeWords;
      $('#learnedWords').textContent=d.learnedWords;
      $('#todayReviews').textContent=d.todayReviews;
      $('#studyDay').textContent=d.studyDay;
      $('#todayPlan').innerHTML=d.todayWords
        ? '<b>'+d.todayWords+'</b> واژه برای مرور امروز باقی مانده است.'
        : 'مرور امروز تمام شده است.';
      $('#tickBars').innerHTML=d.distribution.map((n,i)=>{
        const total=Math.max(1,d.activeWords+d.learnedWords);
        return '<div class="tick-row"><span>'+(i===6?'یادگرفته':i+' تیک')+'</span><div class="bar"><i style="width:'+Math.min(100,n/total*100)+'%"></i></div><b>'+n+'</b></div>';
      }).join('');
    }catch(e){$('#todayPlan').textContent=e.message}
  }

  let state={words:[],index:0,pronunciation:true,locked:false};

  async function study(){
    try{
      const d=await api('api/study.php');
      state.words=d.words||[];
      state.index=0;
      state.pronunciation=d.pronunciation!=='0';
      state.locked=false;
      $('#sessionMeta').textContent='روز '+d.studyDay+' · '+state.words.length+' واژه';
      renderStudy();
    }catch(e){$('#sessionMeta').textContent=e.message}
  }

  function renderStudy(){
    const w=state.words[state.index];
    state.locked=false;
    if(!w){
      $('#wordStage').innerHTML='<div class="empty"><h2>جلسه امروز تمام شد</h2><p>عملکردت ذخیره شد. فردا دوباره ادامه بده.</p><a class="btn primary" href="statistics.php">دیدن آمار</a></div>';
      return;
    }
    $('#progressLabel').textContent=(state.index+1)+' / '+state.words.length;
    $('#progressBar').style.width=((state.index+1)/state.words.length*100)+'%';
    $('#wordEnglish').textContent=w.english;
    $('#wordFarsi').textContent=w.farsi;
    $('#wordDefinition').textContent=w.definition_en||'';
    $('#wordDefinition').classList.toggle('hidden',!w.definition_en);
    $('#wordExampleEn').textContent=w.example_en||'';
    $('#wordExampleEn').classList.toggle('hidden',!w.example_en);
    $('#wordExample').textContent=w.example||'';
    $('#wordExample').classList.toggle('hidden',!w.example);
    const meta=[w.part_of_speech,w.cefr_level,w.phonetic_us?'/'+w.phonetic_us+'/':''].filter(Boolean);
    $('#wordMeta').textContent=meta.join(' · ');
    $('#wordMeta').classList.toggle('hidden',!meta.length);
    $('#answerBox').classList.add('hidden');
    $('#revealBtn').classList.remove('hidden');
    $('#wordHint').textContent=w.ticks+' تیک · '+(w.inReReview?'مرور مجدد':'مرور عادی');
  }

  function initCanvas(){
    const c=$('#writingCanvas');
    if(!c)return;
    const ctx=c.getContext('2d');
    ctx.lineWidth=4;
    ctx.lineCap='round';
    ctx.lineJoin='round';
    let drawing=false;
    const point=e=>{
      const r=c.getBoundingClientRect();
      return {x:(e.clientX-r.left)*(c.width/r.width),y:(e.clientY-r.top)*(c.height/r.height)};
    };
    c.addEventListener('pointerdown',e=>{
      drawing=true;
      c.setPointerCapture?.(e.pointerId);
      const p=point(e);ctx.beginPath();ctx.moveTo(p.x,p.y);e.preventDefault();
    });
    c.addEventListener('pointermove',e=>{
      if(!drawing)return;
      const p=point(e);ctx.lineTo(p.x,p.y);ctx.stroke();e.preventDefault();
    });
    ['pointerup','pointercancel','pointerleave'].forEach(t=>c.addEventListener(t,e=>{
      if(drawing){drawing=false;ctx.closePath();}
    }));
  }

  async function handleStudyClick(e){
    if(e.target.id==='revealBtn'){
      $('#answerBox').classList.remove('hidden');
      e.target.classList.add('hidden');
      if(state.pronunciation){
        const w=state.words[state.index];
        speak(w?.english||$('#wordEnglish')?.textContent||'',w?.audio_us||'');
      }
      return;
    }
    if(e.target.id==='rightBtn'||e.target.id==='wrongBtn'){
      if(state.locked)return;
      state.locked=true;
      const correct=e.target.id==='rightBtn';
      try{
        const result=await post('api/review.php',{word_id:state.words[state.index].id,correct:correct?'1':'0'});
        const current=state.words[state.index];
        current.ticks=Number(result.newTicks??current.ticks);
        current.learned=Boolean(result.learned);
        state.index++;
        renderStudy();
      }catch(x){
        state.locked=false;
        alert(x.message);
      }
      return;
    }
    if(e.target.id==='speakBtn'){
      const w=state.words[state.index];
      if(!speak(w?.english||$('#wordEnglish')?.textContent||'',w?.audio_us||''))alert('مرورگر شما از تلفظ صوتی پشتیبانی نمی‌کند.');
      return;
    }
    if(e.target.id==='writeBtn'){$('#canvasWrap')?.classList.toggle('hidden');return;}
    if(e.target.id==='clearCanvas'){
      const c=$('#writingCanvas');c?.getContext('2d').clearRect(0,0,c.width,c.height);
    }
  }

  function vocabulary(){
    let all=[];
    const render=()=>{
      const q=($('#wordSearch')?.value||'').trim().toLowerCase();
      const f=$('#tickFilter')?.value||'';
      const rows=all.filter(w=>{
        const matches=!q||String(w.english).toLowerCase().includes(q)||String(w.farsi).includes(q);
        const status=!f||(f==='6'?w.learned:String(w.ticks)===f);
        return matches&&status;
      });
      $('#wordList').innerHTML=rows.length?rows.map(w=>
        '<div class="word-item"><div><b>'+esc(w.english)+'</b><br><small>'+esc(w.farsi)+(w.example?' · '+esc(w.example):'')+'</small></div>'+
        '<div class="ticks">'+[0,1,2,3,4,5].map(i=>'<i class="'+(i<w.ticks?'on':'')+'"></i>').join('')+'</div>'+
        '<div class="item-actions"><button class="btn secondary" data-edit-word="'+w.id+'">ویرایش</button><button class="btn danger" data-delete-word="'+w.id+'">حذف</button></div></div>'
      ).join(''):'<div class="empty">واژه‌ای پیدا نشد.</div>';
    };
    const reload=()=>api('api/words.php').then(d=>{all=d.words||[];render()}).catch(e=>$('#wordList').textContent=e.message);
    reload();
    $('#wordSearch')?.addEventListener('input',render);
    $('#tickFilter')?.addEventListener('change',render);
    $('#addWordForm')?.addEventListener('submit',async e=>{
      e.preventDefault();
      const err=$('#wordFormError');err.textContent='';
      const data=Object.fromEntries(new FormData(e.target));
      try{
        const action=data.id?'update':'create';
        await post('api/words.php',new URLSearchParams({action,...data}));
        closeModal('wordModal');e.target.reset();$('#wordModalTitle').textContent='افزودن واژه';$('#wordCancelEdit').classList.add('hidden');await reload();
      }catch(x){err.textContent=x.message}
    });
    document.addEventListener('click',async e=>{
      const edit=e.target.dataset.editWord;
      if(edit){
        const w=all.find(x=>String(x.id)===String(edit));if(!w)return;
        const f=$('#addWordForm');f.elements.id.value=w.id;f.english.value=w.english;f.farsi.value=w.farsi;f.example.value=w.example||'';
        $('#wordModalTitle').textContent='ویرایش واژه';$('#wordCancelEdit').classList.remove('hidden');openModal('wordModal');return;
      }
      const del=e.target.dataset.deleteWord;
      if(del){
        if(!confirm('این واژه حذف شود؟'))return;
        try{await post('api/words.php',new URLSearchParams({action:'delete',id:del}));await reload()}catch(x){alert(x.message)}
      }
      if(e.target.id==='wordCancelEdit'){
        const f=$('#addWordForm');f.reset();f.elements.id.value='';$('#wordModalTitle').textContent='افزودن واژه';e.target.classList.add('hidden');
      }
    });
  }

  async function library(){
    try{
      const d=await api('api/collections.php');
      const collections=d.collections||[];
      $('#collections').innerHTML=collections.map(c=>{
        const safeColor=/^#[0-9a-fA-F]{6}$/.test(c.cover_color||'')?c.cover_color:'#8b7cff';
        const href='collection.php?id='+encodeURIComponent(c.id);
        return '<a class="collection-card" href="'+href+'">'+
          (safeHttpUrl(c.cover_image)?'<img class="collection-cover-image" src="'+esc(safeHttpUrl(c.cover_image))+'" alt="">':'<div class="cover" style="--cover:'+safeColor+'"></div>')+
          '<span class="eyebrow">'+esc(c.type)+'</span><h2>'+esc(c.name)+'</h2><p>'+esc(c.description||'')+'</p>'+
          '<div class="meta"><span>'+Number(c.total_words||0)+' واژه</span><span class="btn secondary">باز کردن مجموعه</span></div></a>';
      }).join('')||'<div class="empty">هنوز مجموعه‌ای اضافه نشده است.</div>';
    }catch(e){$('#collections').textContent=e.message}
  }

  async function collection(){
    const params=new URLSearchParams(location.search);
    const id=Number(params.get('id')||0);
    const title=$('#collectionTitle');
    const meta=$('#collectionMeta');
    const root=$('#collectionContent');
    if(!id){
      title.textContent='مجموعه نامعتبر';
      meta.textContent='';
      root.innerHTML='<div class="form-error">شناسه مجموعه معتبر نیست.</div>';
      return;
    }
    try{
      const [detail,wordData]=await Promise.all([
        api('api/collections.php?id='+encodeURIComponent(id)),
        api('api/collection_words.php?collection_id='+encodeURIComponent(id))
      ]);
      const c=detail.collection;
      const words=wordData.words||[];
      const chapters=c.chapters||[];
      title.textContent=c.name;
      meta.textContent=Number(c.total_words||words.length)+' واژه · '+(c.description||'');
      let body='<div class="collection-content-head"><div><span class="eyebrow">'+esc(c.type)+'</span><h2>فهرست واژه‌ها</h2></div>';
      if(words.length) body+='<button class="btn primary" id="importCollection">افزودن همه به واژگان من</button>';
      body+='</div>';
      if(words.length){
        body+='<div class="library-word-list">'+words.map((w,i)=>{
          const meta=[w.part_of_speech,w.cefr_level,w.phonetic_us?'/'+w.phonetic_us+'/':''].filter(Boolean).join(' · ');
          return '<article class="library-word"><div><span class="word-number">'+(i+1)+'</span><b>'+esc(w.english)+'</b><span>'+esc(w.farsi)+'</span>'+
            (w.example_en?'<small>'+esc(w.example_en)+'</small>':(w.example?'<small>'+esc(w.example)+'</small>':''))+
            (meta?'<small>'+esc(meta)+'</small>':'')+'</div><small>واحد '+Number(w.unit||1)+'</small></article>';
        }).join('')+'</div>';
      }else{
        body+='<div class="empty">این مجموعه هنوز واژه‌ای ندارد.</div>';
      }
      if(chapters.length){
        body+='<div class="story-chapters">'+chapters.map(ch=>'<article><span class="eyebrow">فصل '+Number(ch.chapter_number)+'</span><h3>'+esc(ch.chapter_title)+'</h3><p>'+esc(ch.content).replace(/\n/g,'<br>')+'</p></article>').join('')+'</div>';
      }
      if(safeHttpUrl(c.video_url))body+='<a class="btn secondary media-link" href="'+esc(safeHttpUrl(c.video_url))+'" target="_blank" rel="noopener noreferrer">مشاهده ویدیو</a>';
      if(safeHttpUrl(c.pdf_url))body+='<a class="btn secondary media-link" href="'+esc(safeHttpUrl(c.pdf_url))+'" target="_blank" rel="noopener noreferrer">باز کردن PDF</a>';
      body+='<div id="collectionMessage" class="form-message"></div>';
      root.innerHTML=body;
      $('#importCollection')?.addEventListener('click',async()=>{
        const btn=$('#importCollection');
        btn.disabled=true;
        try{
          const result=await post('api/collection_words.php',new URLSearchParams({collection_id:String(c.id)}));
          $('#collectionMessage').style.color='var(--green)';
          $('#collectionMessage').textContent=(result.added||0)+' واژه به واژگان من اضافه شد.';
        }catch(x){$('#collectionMessage').textContent=x.message}
        finally{btn.disabled=false}
      });
    }catch(e){
      title.textContent='خطا';
      meta.textContent='';
      root.innerHTML='<div class="form-error">'+esc(e.message)+'</div>';
    }
  }

  async function statistics(){
    try{
      const d=await api('api/statistics.php');
      $('#statCards').innerHTML=[['مرور کل',d.totalReviews],['دقت',d.accuracy+'%'],['یادگرفته',d.learnedWords],['رکورد متوالی',d.bestStreak+' روز']].map(x=>'<article class="stat-card"><span>'+x[0]+'</span><strong>'+x[1]+'</strong></article>').join('');
      const max=Math.max(1,...d.daily.map(x=>Number(x.total_reviews)));
      $('#dailyChart').innerHTML=d.daily.map(x=>'<div class="chart-bar"><i style="height:'+Math.max(3,Number(x.total_reviews)/max*170)+'px"></i><span>'+esc(x.date.slice(5))+'</span></div>').join('');
      $('#distribution').innerHTML=d.distribution.map((n,i)=>{
        const total=Math.max(1,d.activeWords+d.learnedWords);
        return '<div class="tick-row"><span>'+(i===6?'یادگرفته':i+' تیک')+'</span><div class="bar"><i style="width:'+Math.min(100,n/total*100)+'%"></i></div><b>'+n+'</b></div>';
      }).join('');
      $('#recentReviews').innerHTML=d.recent.map(x=>'<div class="recent-item"><span>'+esc(x.english)+'</span><span>'+(x.is_correct?'درست':'اشتباه')+'</span></div>').join('')||'<p class="muted">هنوز مروری ثبت نشده.</p>';
    }catch(e){$('#statCards').innerHTML='<div class="empty">'+esc(e.message)+'</div>'}
  }

  async function settings(){
    try{
      const d=await api('api/settings.php');
      const f=$('#settingsForm');
      f.daily_limit.value=d.daily_limit;
      f.pronunciation.checked=d.pronunciation==='1';
      f.addEventListener('submit',async e=>{
        e.preventDefault();
        try{
          await post('api/settings.php',fd(f));
          $('#settingsMessage').style.color='var(--green)';
          $('#settingsMessage').textContent='تنظیمات ذخیره شد.';
        }catch(x){$('#settingsMessage').textContent=x.message}
      });
    }catch(e){$('#settingsMessage').textContent=e.message}
    $('#logoutBtn')?.addEventListener('click',async()=>{
      try{await post('api/auth/logout.php',new URLSearchParams());}catch(_e){}
      location.href='login.php';
    });
  }

  document.addEventListener('click',handleStudyClick);
  document.addEventListener('DOMContentLoaded',initCanvas);

  return {api,auth,dashboard,study,vocabulary,library,collection,statistics,settings,openModal,closeModal,openWordCreate};
})();