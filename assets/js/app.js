// assets/js/app.js — loaded in <head> after api.js
console.log('File app.js đã được tải!');

function openEditStudent(sid) {
  const s = App.getStudents().find(x=>x.id==sid);
  if (!s) { App.toast('Không tìm thấy học sinh','error'); return; }
  const g = id => document.getElementById(id);
  if(g('editStudentId'))   g('editStudentId').value   = s.id;
  if(g('studentName'))     g('studentName').value     = s.name;
  if(g('studentTo'))       g('studentTo').value       = s.to;
  if(g('studentChucVu'))   g('studentChucVu').value   = s.chuc_vu || '';
  if(g('studentRow'))      g('studentRow').value      = s.hang    || '';
  if(g('studentPos'))      g('studentPos').value      = s.vi_tri  || '';
  if(g('deleteStudentBtn'))g('deleteStudentBtn').style.display = '';
  openModal('addStudentModal');
}
