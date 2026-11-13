param([string]$Message = 'Update scoped chain filter repair')
$ErrorActionPreference='Stop'
$taskRoot=Split-Path -Parent $PSScriptRoot
function Invoke-ChainGitApi($endpoint,$method,$payload) {
  $json=$payload | ConvertTo-Json -Depth 10 -Compress
  [IO.File]::WriteAllText("$taskRoot/outputs/chain-filter-repair/api-request.json",$json,[Text.UTF8Encoding]::new($false))
  $result=gh api $endpoint --method $method --input "$taskRoot/outputs/chain-filter-repair/api-request.json"
  if($LASTEXITCODE -ne 0){throw "GitHub request failed: $endpoint"}
  return ($result | ConvertFrom-Json)
}
$ref=gh api repos/Dahelp/ITS/git/ref/heads/codex/chain-filter-repair-20261113 | ConvertFrom-Json
if($LASTEXITCODE -ne 0){throw 'Cannot read branch'}
$parent=gh api "repos/Dahelp/ITS/git/commits/$($ref.object.sha)" | ConvertFrom-Json
if($LASTEXITCODE -ne 0){throw 'Cannot read parent'}
$paths=Get-Content "$taskRoot/outputs/chain-filter-repair/github-files.json" -Raw | ConvertFrom-Json
if($paths.Count -ne 9 -or ($paths | Where-Object {$_ -notlike '.github/chain-filter-repair/*' -and $_ -ne '.github/workflows/deploy-chain-filters.yml'})){throw 'Unexpected commit scope'}
$entries=@()
foreach($p in $paths){
  $blob=Invoke-ChainGitApi 'repos/Dahelp/ITS/git/blobs' 'POST' @{content=[Convert]::ToBase64String([IO.File]::ReadAllBytes((Join-Path $taskRoot $p)));encoding='base64'}
  $entries+=@{path=$p;mode='100644';type='blob';sha=$blob.sha}
}
$tree=Invoke-ChainGitApi 'repos/Dahelp/ITS/git/trees' 'POST' @{base_tree=$parent.tree.sha;tree=$entries}
$commit=Invoke-ChainGitApi 'repos/Dahelp/ITS/git/commits' 'POST' @{message=$Message;tree=$tree.sha;parents=@($ref.object.sha)}
$updated=Invoke-ChainGitApi 'repos/Dahelp/ITS/git/refs/heads/codex/chain-filter-repair-20261113' 'PATCH' @{sha=$commit.sha;force=$false}
$updated.object.sha
