const HashttickAdmin=(()=> {
  const $=s=>document.querySelector(s);
  const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  const csrf=()=>document.querySelector('meta[name="csrf-token"]')?.content||'';

  const api=(url,opt={})=>Hashttick.api(url,opt);

  const post=(action,data)=>api('api/admin.php?action='+action,{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams(data)
  });

  let current=null;

  function setCollectionForm(c=null){
    const f=$('#collectionForm');
    f.reset();
    f.elements.id.value=c?.id||'';
    f.name.value=c?.name||'';
    f.description.value=c?.description||'';
    f.type.value=c?.type||'dictionary';
    f.cover_color.value=(c?.cover_color&&/^#[0-9a-fA-F]{6}$/.test(c.cover_color))?c.cover_color:'#8b7cff';
    f.cover_image.value=c?.cover_image||'';
    f.video_url.value=c?.video_url||'';
    f.pdf_url.value=c?.pdf_url||'';
    $('#collectionModalTitle').textContent=c?'ویرایش مجموعه':'ساخت مجموعه';
    $('#collectionSubmit').textContent=c?'ذخیره تغییرات':'ساخت';
    $('#adminError').textContent='';
  }

  function resetContentForms(){
    $('#wordForm').reset();$('#wordForm').elements.id.value='';
    $('#storyForm').reset();$('#storyForm').elements.id.value='';
    $('#wordSubmit').textContent='افزودن واژه';
    $('#storySubmit').textContent='افزودن فصل';
    $('#wordCancelEdit').classList.add('hidden');
    $('#storyCancelEdit').classList.add('hidden');
    $('#contentError').textContent='';
  }

  async function load(){
    const d=await api('api/admin.php?action=collections');
    $('#adminCollectionList').innerHTML=(d.collections||[]).map(c=>
      '<div class="word-item"><div><b>'+esc(c.name)+'</b><br><small>'+esc(c.type)+' · '+Number(c.total_words||0)+' واژه</small></div>'+
      '<div class="item-actions"><button class="btn secondary" data-edit-collection="'+c.id+'">ویرایش</button><button class="btn secondary" data-manage="'+c.id+'">مدیریت محتوا</button><button class="btn danger" data-delete-collection="'+c.id+'">حذف</button></div></div>'
    ).join('')||'<div class="empty">مجموعه‌ای وجود ندارد.</div>';

    const u=await api('api/admin.php?action=users');
    $('#adminUserList').innerHTML=(u.users||[]).map(x=>
      '<div class="word-item"><div><b>'+esc(x.username)+'</b><br><small>'+esc(x.email)+'</small></div><span class="status-pill">'+(x.is_admin?'ADMIN':'USER')+'</span></div>'
    ).join('')||'<div class="empty">کاربری وجود ندارد.</div>';
  }

  async function openCollection(id){
    const d=await api('api/admin.php?action=collection&id='+encodeURIComponent(id));
    current=d.collection;
    $('#manageTitle').textContent=current.name;
    resetContentForms();

    $('#manageWords').innerHTML=(d.words||[]).map(w=>
      '<div class="word-item"><div><b>'+esc(w.english)+'</b><br><small>'+esc(w.farsi)+(w.example?' · '+esc(w.example):'')+' · واحد '+Number(w.unit||1)+'</small></div>'+
      '<div class="item-actions"><button class="btn secondary" data-edit-content-word="'+w.id+'">ویرایش</button><button class="btn danger" data-word-delete="'+w.id+'">حذف</button></div></div>'
    ).join('')||'<div class="empty">واژه‌ای ندارد.</div>';

    $('#manageStories').innerHTML=(d.stories||[]).map(s=>
      '<div class="word-item"><div><b>فصل '+Number(s.chapter_number)+' — '+esc(s.chapter_title)+'</b></div>'+
      '<div class="item-actions"><button class="btn secondary" data-edit-story="'+s.id+'">ویرایش</button><button class="btn danger" data-story-delete="'+s.id+'">حذف</button></div></div>'
    ).join('')||'<div class="empty">داستانی ندارد.</div>';

    $('#contentManager').classList.remove('hidden');
  }

  function openCollectionCreate(){
    setCollectionForm(null);
    Hashttick.openModal('collectionModal');
  }

  function editCollectionFromCache(id){
    api('api/admin.php?action=collection&id='+encodeURIComponent(id)).then(d=>{
      setCollectionForm(d.collection);
      Hashttick.openModal('collectionModal');
    }).catch(e=>$('#adminError').textContent=e.message);
  }

  function editWord(id){
    const row=[...document.querySelectorAll('[data-edit-content-word]')].find(x=>String(x.dataset.editContentWord)===String(id));
    if(!row)return;
    api('api/admin.php?action=collection&id='+encodeURIComponent(current.id)).then(d=>{
      const w=(d.words||[]).find(x=>String(x.id)===String(id));if(!w)return;
      const f=$('#wordForm');f.elements.id.value=w.id;f.english.value=w.english;f.farsi.value=w.farsi;f.example.value=w.example||'';f.unit.value=w.unit||1;
      $('#wordSubmit').textContent='ذخیره تغییرات';$('#wordCancelEdit').classList.remove('hidden');$('#contentError').textContent='';
      f.english.focus();
    }).catch(e=>$('#contentError').textContent=e.message);
  }

  function editStory(id){
    api('api/admin.php?action=collection&id='+encodeURIComponent(current.id)).then(d=>{
      const s=(d.stories||[]).find(x=>String(x.id)===String(id));if(!s)return;
      const f=$('#storyForm');f.elements.id.value=s.id;f.chapter_number.value=s.chapter_number;f.chapter_title.value=s.chapter_title;f.content.value=s.content;
      $('#storySubmit').textContent='ذخیره تغییرات';$('#storyCancelEdit').classList.remove('hidden');$('#contentError').textContent='';
      f.chapter_title.focus();
    }).catch(e=>$('#contentError').textContent=e.message);
  }

  async function init(){
    document.querySelectorAll('[data-tab]').forEach(b=>b.onclick=()=>{
      document.querySelectorAll('[data-tab]').forEach(x=>x.classList.remove('active'));
      b.classList.add('active');
      $('#adminCollections').classList.toggle('hidden',b.dataset.tab!=='collections');
      $('#adminUsers').classList.toggle('hidden',b.dataset.tab!=='users');
    });

    $('#collectionForm')?.addEventListener('submit',async e=>{
      e.preventDefault();const data=Object.fromEntries(new FormData(e.target));const action=data.id?'update_collection':'create_collection';
      try{await post(action,data);Hashttick.closeModal('collectionModal');await load()}catch(x){$('#adminError').textContent=x.message}
    });

    $('#wordForm')?.addEventListener('submit',async e=>{
      e.preventDefault();if(!current)return;
      const data=Object.fromEntries(new FormData(e.target));const action=data.id?'update_word':'create_word';
      try{await post(action,{collection_id:current.id,...data});resetContentForms();await openCollection(current.id)}catch(x){$('#contentError').textContent=x.message}
    });

    $('#storyForm')?.addEventListener('submit',async e=>{
      e.preventDefault();if(!current)return;
      const data=Object.fromEntries(new FormData(e.target));const action=data.id?'update_story':'create_story';
      try{await post(action,{collection_id:current.id,...data});resetContentForms();await openCollection(current.id)}catch(x){$('#contentError').textContent=x.message}
    });

    document.addEventListener('click',async e=>{
      if(e.target.id==='closeManager'){$('#contentManager').classList.add('hidden');return;}
      if(e.target.id==='wordCancelEdit'||e.target.id==='storyCancelEdit'){resetContentForms();return;}
      if(e.target.dataset.manage){openCollection(e.target.dataset.manage);return;}
      if(e.target.dataset.editCollection){editCollectionFromCache(e.target.dataset.editCollection);return;}
      if(e.target.dataset.deleteCollection){
        if(confirm('مجموعه حذف شود؟')){try{await post('delete_collection',{id:e.target.dataset.deleteCollection});await load();$('#contentManager').classList.add('hidden')}catch(x){alert(x.message)}}
        return;
      }
      if(e.target.dataset.editContentWord){editWord(e.target.dataset.editContentWord);return;}
      if(e.target.dataset.wordDelete){
        if(confirm('واژه حذف شود؟')){try{await post('delete_word',{id:e.target.dataset.wordDelete,collection_id:current.id});await openCollection(current.id)}catch(x){alert(x.message)}}
        return;
      }
      if(e.target.dataset.editStory){editStory(e.target.dataset.editStory);return;}
      if(e.target.dataset.storyDelete){
        if(confirm('فصل حذف شود؟')){try{await post('delete_story',{id:e.target.dataset.storyDelete});await openCollection(current.id)}catch(x){alert(x.message)}}
      }
    });

    load().catch(e=>$('#adminCollectionList').innerHTML='<div class="form-error">'+esc(e.message)+'</div>');
  }

  return {init,openCollectionCreate};
})();