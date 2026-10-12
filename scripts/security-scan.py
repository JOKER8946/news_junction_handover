"""Redacted heuristic scan of reachable Git blobs, including compressed SQL backups."""
import gzip, json, re, subprocess
from pathlib import Path

def git(*args):
    return subprocess.check_output(['git', *args])

patterns = {
    'provider_token': re.compile(rb'(?:AKIA[0-9A-Z]{16}|AIza[0-9A-Za-z_-]{35}|gh[pousr]_[0-9A-Za-z]{30,}|sk_(?:live|test)_[0-9A-Za-z]{20,}|sk-proj-[0-9A-Za-z_-]{30,}|xox[baprs]-[0-9A-Za-z-]{20,})'),
    'private_key': re.compile(rb'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----'),
    'credential_assignment': re.compile(rb'(?i)(?:password|passwd|api[_-]?key|api[_-]?secret|appSecret|access[_-]?token|client[_-]?secret|smtp[_-]?pass)\s*[\x22\x27]?\s*\]?\s*[:=]\s*[\x22\x27]([^\x22\x27\r\n]{8,})[\x22\x27]'),
}
placeholder = re.compile(rb'(?i)example|replace|your[_ -]|access_token|app_secret|facebook_app|test|dummy|sample|placeholder|password|process\.|getenv|\$|function|undefined|null|secret-key|changeme|integration|hash|bcrypt')
objects = {}
for line in git('rev-list', '--objects', '--all').decode('utf-8', 'replace').splitlines():
    oid, _, name = line.partition(' ')
    if name: objects[oid] = name
tracked = set(git('ls-files', '-z').decode().split('\0'))
heads = {line.split()[2] for line in git('ls-tree', '-r', 'HEAD').decode().splitlines() if len(line.split()) >= 3}
check = subprocess.run(['git','cat-file','--batch-check=%(objectname) %(objecttype) %(objectsize)'],input=('\n'.join(objects)+'\n').encode(),stdout=subprocess.PIPE,check=True)
blobs = [(a,int(c)) for a,b,c in (line.split() for line in check.stdout.decode().splitlines()) if b=='blob']
proc = subprocess.Popen(['git','cat-file','--batch'],stdin=subprocess.PIPE,stdout=subprocess.PIPE)
findings=[]; scanned=0; skipped_binary=0; archives=[]
for oid,size in blobs:
    name=objects[oid]
    proc.stdin.write((oid+'\n').encode());proc.stdin.flush();proc.stdout.readline()
    content=proc.stdout.read(size);proc.stdout.read(1)
    if name.endswith('.sql.gz'):
        archives.append({'path':name,'object':oid[:12],'in_head':oid in heads})
        content=gzip.decompress(content) if content.startswith(b'\x1f\x8b') else content
    if b'\0' in content[:8000]: skipped_binary+=1;continue
    scanned+=1
    for kind,pattern in patterns.items():
        matches=[m for m in pattern.finditer(content) if kind!='credential_assignment' or not placeholder.search(m.group(1))]
        if matches: findings.append({'path':name,'object':oid[:12],'in_head':oid in heads,'kind':kind,'count':len(matches)})
proc.stdin.close();proc.wait()
local_backups=[]
for filename in Path('database').glob('*.sql.gz'):
    with filename.open('rb') as source: magic=source.read(2)
    if magic!=b'\x1f\x8b': continue
    counts={key:0 for key in patterns};size=0;tail=b''
    with gzip.open(filename,'rb') as source:
        while True:
            chunk=source.read(1024*1024)
            if not chunk:break
            size+=len(chunk);content=tail+chunk
            for kind,pattern in patterns.items():
                counts[kind]+=sum(1 for m in pattern.finditer(content) if m.end()>len(tail) and (kind!='credential_assignment' or not placeholder.search(m.group(1))))
            tail=content[-4096:]
    local_backups.append({'path':filename.as_posix(),'decoded_bytes':size,'potential_secrets':counts})
report={'note':'Potential secrets only; values are never written. Heuristics can miss secrets and include fixtures. Reachable refs only; deleted remote forks/caches are not scanned.','text_blobs_scanned':scanned,'binary_blobs_skipped':skipped_binary,'archives':archives,'local_backups':local_backups,'findings':findings}
Path('tmp').mkdir(exist_ok=True)
Path('tmp/security-history-scan.json').write_text(json.dumps(report,indent=2))
print(json.dumps({'text_blobs_scanned':scanned,'binary_blobs_skipped':skipped_binary,'archive_versions':len(archives),'local_backups':local_backups,'finding_groups':len(findings),'non_vendor_findings':[x for x in findings if '/vendor/' not in x['path'] and 'tinymce' not in x['path'] and 'tiny.7' not in x['path']][:50],'report':'tmp/security-history-scan.json'},indent=2))
