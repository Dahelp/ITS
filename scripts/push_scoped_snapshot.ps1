param([Parameter(Mandatory=$true)][string]$PlanFile,[Parameter(Mandatory=$true)][string]$Branch,[Parameter(Mandatory=$true)][string]$Base,[Parameter(Mandatory=$true)][string]$Message)
$ErrorActionPreference='Stop'
$taskRoot=Split-Path -Parent $PSScriptRoot
$taskOutput=Join-Path $taskRoot 'outputs/project-publication'
function Invoke-TaskGit { & git -c safe.directory=C:/xampp82/htdocs/its-center -c core.autocrlf=false -c core.safecrlf=false @args; if($LASTEXITCODE -ne 0){throw "Git failed: $($args[0])"} }
$items=Get-Content -LiteralPath $PlanFile -Raw | ConvertFrom-Json
$paths=@($items | ForEach-Object {if($_ -is [string]){$_}else{$_.path}})
if(!$paths.Count -or ($paths | Where-Object {$_ -match '(^/|\.\.|\\|\x00)' })){throw 'Invalid explicit file list'}
$taskIndex=Join-Path $taskOutput ('index-'+[Guid]::NewGuid().ToString('N'))
$taskPathList=Join-Path $taskOutput 'snapshot-paths.bin'
[IO.File]::WriteAllBytes($taskPathList,[Text.Encoding]::UTF8.GetBytes(($paths -join "`0")+"`0"))
$oldIndex=$env:GIT_INDEX_FILE
try {
  $env:GIT_INDEX_FILE=$taskIndex
  Invoke-TaskGit read-tree $Base | Out-Null
  Invoke-TaskGit add -A -f "--pathspec-from-file=$taskPathList" --pathspec-file-nul | Out-Null
  $tree=(Invoke-TaskGit write-tree).Trim()
  $body=Join-Path $taskOutput 'commit-message.txt'
  [IO.File]::WriteAllText($body,$Message,[Text.UTF8Encoding]::new($false))
  $commit=(Invoke-TaskGit commit-tree $tree -p $Base -F $body).Trim()
  $changed=@(Invoke-TaskGit diff-tree --no-commit-id --name-only -r $commit)
  $unexpected=@($changed | Where-Object {$_ -notin $paths})
  if($unexpected.Count){throw 'Commit contains files outside explicit list'}
  [IO.File]::WriteAllText((Join-Path $taskOutput 'latest-commit.json'),(@{sha=$commit;parent=$Base;branch=$Branch;files=$changed} | ConvertTo-Json -Depth 6),[Text.UTF8Encoding]::new($false))
  Write-Output "SCOPED_COMMIT $commit FILES $($changed.Count)"
  Invoke-TaskGit push origin "${commit}:refs/heads/$Branch"
} finally {
  if($null -eq $oldIndex){Remove-Item Env:GIT_INDEX_FILE -ErrorAction SilentlyContinue}else{$env:GIT_INDEX_FILE=$oldIndex}
}
