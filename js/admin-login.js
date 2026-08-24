(() => {
  const overlay=document.getElementById('adminLogin');
  const form=document.getElementById('adminLoginForm');
  if(!overlay||!form)return;
  async function check(){try{const r=await fetch('api/auth.php',{cache:'no-store'});const d=await r.json();if(d.authenticated){document.body.classList.remove('admin-locked');overlay.remove();}}catch(e){}}
  form.addEventListener('submit',async e=>{e.preventDefault();const input=document.getElementById('adminPassword'),status=document.getElementById('loginStatus');status.textContent='Memeriksa...';status.className='login-status';try{const r=await fetch('api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({password:input.value})});const d=await r.json();if(d.ok){document.body.classList.remove('admin-locked');overlay.remove();}else{status.textContent=d.message||'Password salah.';status.className='login-status error';input.value='';input.focus();}}catch(err){status.textContent='Server belum terhubung. Jalankan website melalui PHP/hosting.';status.className='login-status error';}});
  document.getElementById('togglePassword')?.addEventListener('click',()=>{const i=document.getElementById('adminPassword');i.type=i.type==='password'?'text':'password';});document.getElementById('logoutBtn')?.addEventListener('click',async()=>{await fetch('api/auth.php?action=logout',{method:'POST'});location.reload();});check();
})();
