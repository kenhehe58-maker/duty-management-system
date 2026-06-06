// ============================================================
//  assets/js/api.js  — API gateway (thay thế AppStore)
// ============================================================
const App = (() => {
  'use strict';

  // ── POST helper ───────────────────────────────────────────
  async function post(url, body) {
    try {
      const r = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      const text = await r.text();
      try { return JSON.parse(text); }
      catch { console.error('Non-JSON response from', url, ':', text.substring(0,200)); return { ok:false, error:'Server error' }; }
    } catch(e) { return { ok:false, error:e.message }; }
  }

  // ── GET helper ────────────────────────────────────────────
  async function get(url) {
    try {
      const r = await fetch(url);
      const text = await r.text();
      try { return JSON.parse(text); }
      catch { console.error('Non-JSON from', url); return []; }
    } catch(e) { return []; }
  }

  // ── Toast ─────────────────────────────────────────────────
  function toast(msg, type='success') {
    const c = document.getElementById('toastContainer');
    if (!c) return;
    const el = document.createElement('div');
    el.className = 'toast toast-'+type;
    el.textContent = msg;
    c.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => { el.classList.remove('show'); setTimeout(()=>el.remove(),300); }, 3000);
  }

  // ── Modal helpers ─────────────────────────────────────────
  function openModal(id)  { document.getElementById(id)?.classList.remove('hidden'); }
  function closeModal(id) { document.getElementById(id)?.classList.add('hidden'); }

  // ── Students ──────────────────────────────────────────────
  let _students = [];
  async function loadStudents() {
    _students = await get('api/v1/students.php');
    return _students;
  }
  function getStudents() { return _students; }

  async function saveStudent(data) {
    return post('api/v1/students.php', { action:'upsert', student:data });
  }
  async function deleteStudent(id) {
    return post('api/v1/students.php', { action:'delete', id });
  }
  async function assignDesk(sid, to, row, pos) {
    return post('api/v1/students.php', { action:'assign', sid, to, hang:row, vi_tri:pos });
  }
  async function autoAssignTo(to) {
    return post('api/v1/students.php', { action:'auto_assign', to });
  }
  async function importStudents(rows, mode) {
    return post('api/v1/students.php', { action:'import', students:rows, mode });
  }

  // ── Schedule ──────────────────────────────────────────────
  async function addDutyPerson(week, day, sid) {
    return post('api/v1/schedule.php', { action:'add', week, day, sid });
  }
  async function removeDutyPerson(week, day, sid) {
    return post('api/v1/schedule.php', { action:'remove', week, day, sid });
  }
  async function reorderDuty(week, day, sids) {
    return post('api/v1/schedule.php', { action:'reorder', week, day, sids });
  }
  async function regenerateWeek(week) {
    return post('api/v1/schedule.php', { action:'regen', week });
  }
  async function addExtraDay(week, date, to) {
    return post('api/v1/schedule.php', { action:'add_extra', week, date, to });
  }
  async function removeExtraDay(week, idx) {
    return post('api/v1/schedule.php', { action:'remove_extra', week, idx });
  }

  // ── Rules ─────────────────────────────────────────────────
  async function saveRules(rules) {
    return post('api/v1/rules.php', { rules });
  }

  // ── Reports ───────────────────────────────────────────────
  async function deletePhoto(key, date, path) {
    return post('api/v1/report.php', { action:'delete_photo', key, date, path });
  }

  // ── Init ──────────────────────────────────────────────────
  function init() {
    // backdrop click closes modals
    document.addEventListener('click', e => {
      if (e.target.classList.contains('popup')) closeModal(e.target.id);
    });
    loadStudents();
  }

  return {
    init, post, get, toast, openModal, closeModal,
    loadStudents, getStudents,
    saveStudent, deleteStudent, assignDesk, autoAssignTo, importStudents,
    addDutyPerson, removeDutyPerson, reorderDuty, regenerateWeek, addExtraDay, removeExtraDay,
    saveRules, deletePhoto,
  };
})();

// Backwards compat alias
const AppStore = {
  init: App.init,
  getStudents: App.getStudents,
  saveStudent: App.saveStudent,
  deleteStudent: App.deleteStudent,
  assignDesk: App.assignDesk,
  autoAssignTo: App.autoAssignTo,
  importStudents: App.importStudents,
  addDutyPerson: App.addDutyPerson,
  removeDutyPerson: App.removeDutyPerson,
  reorderDuty: App.reorderDuty,
  regenerateWeek: App.regenerateWeek,
  addExtraDay: App.addExtraDay,
  removeExtraDay: App.removeExtraDay,
  saveRules: App.saveRules,
  deletePhoto: App.deletePhoto,
};