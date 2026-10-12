const https=require('node:https');
const dns=require('node:dns').promises;
const net=require('node:net');
function publicAddress(ip) {
  if(net.isIP(ip)!==4)return false; // Deliberately restrict feed egress to public IPv4.
  const [a,b]=ip.split('.').map(Number);
  return !(a===0||a===10||a===127||a>=224||(a===169&&b===254)||(a===172&&b>=16&&b<=31)||(a===192&&b===168)||(a===100&&b>=64&&b<=127)||a===198&&[18,19,51].includes(b)||a===192&&b===0||a===203&&b===0);
}
async function fetchFeed(url) {
  const target=new URL(url);
  if(target.protocol!=='https:'||target.username||target.password||target.port&&target.port!=='443')throw Error('Feeds must use public HTTPS URLs on port 443.');
  const records=await dns.lookup(target.hostname,{all:true,family:4});
  if(!records.length||records.some(r=>!publicAddress(r.address)))throw Error('Feed address is not a permitted public IPv4 address.');
  // Pin the checked address to prevent DNS rebinding. Redirects are rejected, never followed.
  return new Promise((resolve,reject)=>{
    const request=https.get(target,{lookup:(host,options,cb)=>options.all?cb(null,[records[0]]):cb(null,records[0].address,4),headers:{'User-Agent':'NewsJunction/2.0 RSS Reader'}},res=>{
      if(res.statusCode!==200){res.resume();reject(Error('Feed HTTP '+res.statusCode));return;}
      let size=0;const chunks=[];
      res.on('data',chunk=>{size+=chunk.length;if(size>5*1024*1024)request.destroy(Error('Feed exceeds 5 MB'));else chunks.push(chunk);});
      res.on('end',()=>resolve(Buffer.concat(chunks).toString('utf8')));res.on('error',reject);
    });
    const timer=setTimeout(()=>request.destroy(Error('Feed timed out')),20000);timer.unref();
    request.on('close',()=>clearTimeout(timer));request.on('error',reject);
  });
}
module.exports={publicAddress,fetchFeed};
