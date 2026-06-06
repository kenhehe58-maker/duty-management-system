// ============================================================
//  assets/js/api.js  — API gateway + global helpers
//  Load trong <head> để openModal/closeModal sẵn sàng khi DOM render
// ============================================================

// ── Global modal functions (cần sẵn sàng ngay) ───────────────
function openModal(id)  { const el=document.getElementById(id); if(el) el.classList.remove('hidden'); }
function closeModal(id) { const el=document.getElementById(id); if(el) el.classList.add('hidden'); }

const App = (() => {
  'use strict';
  let _students = [];

  async function post(url, body) {
    try {
      const r = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      const text = await r.text();
      try { return JSON.parse(text); }
      catch(e) {
        console.error('Non-JSON response from', url, ':', text.substring(0,300));
        return { ok:false, error:'Server error (non-JSON)' };
      }
    } catch(e) { return { ok:false, error:e.message }; }
  }

  async function get(url) {
    try {
      const r = await fetch(url);
      const text = await r.text();
      try { return JSON.parse(text); }
      catch(e) { console.error('Non-JSON from GET', url); return []; }
    } catch(e) { return []; }
  }

  function toast(msg, type='success') {
    const c = document.getElementById('toastContainer');
    if (!c) { console.log('[TOAST]', msg); return; }
    const el = document.createElement('div');
    el.className = 'toast toast-'+type;
    el.textContent = msg;
    c.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => { el.classList.remove('show'); setTimeout(()=>el.remove(),300); }, 3000);
  }

  async function loadStudents() {
    _students = await get('api/v1/students.php');
    return _students;
  }
  function getStudents() { return _students; }

  async function saveStudent(data)       { return post('api/v1/students.php', { action:'upsert',  student:data }); }
  async function deleteStudent(id)       { return post('api/v1/students.php', { action:'delete',  id }); }
  async function assignDesk(sid,to,h,v)  { return post('api/v1/students.php', { action:'assign',  sid, to, hang:h, vi_tri:v }); }
  async function autoAssignTo(to)        { return post('api/v1/students.php', { action:'auto_assign', to }); }
  async function importStudents(rows,mode){ return post('api/v1/students.php', { action:'import', students:rows, mode }); }

  async function addDutyPerson(w,d,sid)  { return post('api/v1/schedule.php', { action:'add',          week:w, day:d, sid }); }
  async function removeDutyPerson(w,d,s) { return post('api/v1/schedule.php', { action:'remove',       week:w, day:d, sid:s }); }
  async function reorderDuty(w,d,sids)   { return post('api/v1/schedule.php', { action:'reorder',      week:w, day:d, sids }); }
  async function regenerateWeek(w)       { return post('api/v1/schedule.php', { action:'regen',        week:w }); }
  async function addExtraDay(w,date,to)  { return post('api/v1/schedule.php', { action:'add_extra',    week:w, date, to }); }
  async function removeExtraDay(w,idx)   { return post('api/v1/schedule.php', { action:'remove_extra', week:w, idx }); }

  async function saveRules(rules)        { return post('api/v1/rules.php', { rules }); }
  async function deletePhoto(key,dt,path){ return post('api/v1/report.php', { action:'delete_photo', key, date:dt, path }); }

  function init() {
    // Đóng modal khi click backdrop
    document.addEventListener('click', e => {
      if (e.target.classList.contains('popup')) closeModal(e.target.id);
    });
    loadStudents();
  }

  return {
    init, post, get, toast,
    openModal, closeModal,
    loadStudents, getStudents,
    saveStudent, deleteStudent, assignDesk, autoAssignTo, importStudents,
    addDutyPerson, removeDutyPerson, reorderDuty, regenerateWeek, addExtraDay, removeExtraDay,
    saveRules, deletePhoto,
  };
})();
