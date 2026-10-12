const {query}=require('./database');
function reserveUpload(bytes) {
  return async(req,res,next)=>{
    try {
      const limit=Number(process.env.MAX_UPLOAD_BYTES_PER_MONTH || 2147483648);
      if(!Number.isSafeInteger(limit) || limit<0) throw Error('Invalid upload budget configuration');
      const bucket=new Date().toISOString().slice(0,7);
      const result=await query(`INSERT INTO upload_budgets(month,bytes) SELECT $1,$2::bigint WHERE $2::bigint <= $3::bigint
       ON CONFLICT(month) DO UPDATE SET bytes=upload_budgets.bytes+EXCLUDED.bytes
       WHERE upload_budgets.bytes+EXCLUDED.bytes <= $3::bigint RETURNING bytes`,[bucket,bytes,limit]);
      if(!result.rowCount)return res.status(429).json({error:'Monthly upload allowance reached. Contact the administrator.'});
      let released=false;
      const settle=()=>{
        if(released)return;released=true;
        const used=res.statusCode<400 && res.writableFinished && req.file ? req.file.size : 0;
        query('UPDATE upload_budgets SET bytes=GREATEST(0,bytes-$2::bigint) WHERE month=$1',[bucket,Math.max(0,bytes-used)])
          .catch(()=>console.error('Upload allowance settlement failed; reservation retained.'));
      };
      res.once('finish',settle);res.once('close',settle);next();
    }catch(e){next(e);}
  };
}
module.exports={reserveUpload};
