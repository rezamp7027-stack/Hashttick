const Hashttick=(()=> {
  const $=s=>document.querySelector(s);
  const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  const csrf=()=>document.querySelector('meta[name="csrf-token"]')?.content||'';

  async function api(url,opt={},allowRefresh=true){
    const method=String(opt.method||'GET').toUpperCase();
    const headers=new Headers(opt.headers||{});
    if(method!=='GET'&&method!=='HEAD') headers.set('X-CSRF-Token',csrf());
    const request={credentials:'same-origin',...opt,headers};
    const r=await fetch(url,request);
    const d=await r.json().catch(()=>({success:false,error:'پاسخ نامعتبر از سرور'}));

    if(r.status===401&&allowRefresh&&!url.includes('api/auth/refresh.php')){
      try{
        const rr=await fetch('api/auth/refresh.php',{
          method:'POST',
          credentials:'same-origin',
          headers:{'X-CSRF-Token':csrf()}
        });
        const rd=await rr.json().catch(()=>({success:false}));
        if(rr.ok&&rd.success)return api(url,opt,false);
      }catch(_e){}
      if(d.redirect){location.href=d.redirect;throw new Error(d.error||'جلسه منقضی شده است');}
    }
    if(r.status===401&&d.redirect){location.href=d.redirect;throw new Error(d.error||'لطفاً وارد شوید');}
    if(!r.ok||d.success===false)throw new Error(d.error||'خطا');
    return d;
  }

  const fd=f=>new URLSearchParams(new FormData(f));
  const post=(url,data)=>api(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:data instanceof URLSearchParams?data:new URLSearchParams(data)});

  function openModal(id){$('#'+id)?.classList.remove('hidden')}
  function closeModal(id){$('#'+id)?.classList.add('hidden')}

  function speak(text){
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
    $('#wordExample').textContent=w.example||'';
    $('#wordExample').classList.toggle('hidden',!w.example);
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
      if(state.pronunciation)speak($('#wordEnglish')?.textContent||'');
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
      if(!speak($('#wordEnglish')?.textContent||''))alert('مرورگر شما از تلفظ صوتی پشتیبانی نمی‌کند.');
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
        return '<article class="collection-card">'+
          (c.cover_image?'<img class="collection-cover-image" src="'+esc(c.cover_image)+'" alt="">':'<div class="cover" style="--cover:'+safeColor+'"></div>')+
          '<span class="eyebrow">'+esc(c.type)+'</span><h2>'+esc(c.name)+'</h2><p>'+esc(c.description||'')+'</p>'+
          '<div class="meta"><span>'+Number(c.total_words||0)+' واژه</span><button class="btn secondary" data-collection="'+c.id+'">باز کردن</button></div></article>';
      }).join('')||'<div class="empty">هنوز مجموعه‌ای اضافه نشده است.</div>';

      document.querySelectorAll('[data-collection]').forEach(b=>b.addEventListener('click',async()=>{
        const id=b.dataset.collection;
        const reader=$('#storyReader');
        reader.classList.remove('hidden');
        reader.innerHTML='<div class="empty">در حال بارگذاری محتوا...</div>';
        try{
          const [detail,wordData]=await Promise.all([
            api('api/collections.php?id='+encodeURIComponent(id)),
            api('api/collection_words.php?collection_id='+encodeURIComponent(id))
          ]);
          const c=detail.collection;
          const words=wordData.words||[];
          const chapters=c.chapters||[];
          let body='';
          if(words.length){
            body+='<div class="collection-content-head"><h3>واژه‌های این مجموعه</h3><button class="btn primary" data-import-collection="'+c.id+'">افزودن به واژگان من</button></div>';
            body+='<div class="library-word-list">'+words.map(w=>
              '<div class="library-word"><div><b>'+esc(w.english)+'</b><span>'+esc(w.farsi)+'</span>'+(w.example?'<small>'+esc(w.example)+'</small>':'')+'</div><small>واحد '+Number(w.unit||1)+'</small></div>'
            ).join('')+'</div>';
          }
          if(chapters.length){
            body+='<div class="story-chapters">'+chapters.map(ch=>'<article><span class="eyebrow">فصل '+Number(ch.chapter_number)+'</span><h3>'+esc(ch.chapter_title)+'</h3><p>'+esc(ch.content).replace(/\n/g,'<br>')+'</p></article>').join('')+'</div>';
          }
          if(c.video_url)body+='<a class="btn secondary media-link" href="'+esc(c.video_url)+'" target="_blank" rel="noopener noreferrer">مشاهده ویدیو</a>';
          if(c.pdf_url)body+='<a class="btn secondary media-link" href="'+esc(c.pdf_url)+'" target="_blank" rel="noopener noreferrer">باز کردن PDF</a>';
          reader.innerHTML='<div class="panel-head"><div><span class="eyebrow">'+esc(c.type)+'</span><h2>'+esc(c.name)+'</h2></div><button id="closeReader" class="btn ghost">بستن</button></div>'+
            (body||'<p class="muted">این مجموعه هنوز محتوایی ندارد.</p>')+'<div id="libraryMessage" class="form-message"></div>';
          $('#closeReader').onclick=()=>reader.classList.add('hidden');
          reader.querySelector('[data-import-collection]')?.addEventListener('click',async()=>{
            const btn=reader.querySelector('[data-import-collection]');btn.disabled=true;
            try{
              const result=await post('api/collection_words.php',new URLSearchParams({collection_id:c.id}));
              $('#libraryMessage').style.color='var(--green)';
              $('#libraryMessage').textContent=(result.added||0)+' واژه به واژگان من اضافه شد.';
            }catch(x){$('#libraryMessage').textContent=x.message}
            finally{btn.disabled=false}
          });
        }catch(x){reader.innerHTML='<div class="form-error">'+esc(x.message)+'</div>'}
      }));
    }catch(e){$('#collections').textContent=e.message}
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

  return {api,auth,dashboard,study,vocabulary,library,statistics,settings,openModal,closeModal};
})();