const assert=require('node:assert/strict'),{webcrypto}=require('node:crypto'),{generatePassword}=require('../public/js/account-password.js');
const generated=new Set();
for(let i=0;i<100;i++){const value=generatePassword(webcrypto);assert.equal(value.length,18);assert(/[A-Z]/.test(value));assert(/[a-z]/.test(value));assert(/[2-9]/.test(value));assert(/[!@#$%]/.test(value));assert(!generated.has(value));generated.add(value);}
assert.throws(()=>generatePassword(null),/Secure randomness/);
console.log('Password generation: secure randomness required, 18 characters, all character groups and unique samples OK; no credentials stored');
