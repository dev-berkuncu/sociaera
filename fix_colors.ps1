$files = Get-ChildItem -Path 'c:\Users\berku\.gemini\antigravity\scratch\sociaera\public' -Recurse -Include '*.php' | Where-Object { $_.DirectoryName -notlike '*\admin*' }

foreach ($f in $files) {
    $content = Get-Content $f.FullName -Raw
    $updated = $content `
        -replace '#1E293B', '#2a2a2b' `
        -replace '#1e293b', '#2a2a2b' `
        -replace 'rgba\(255,\s*107,\s*53,', 'rgba(255, 145, 0,' `
        -replace 'rgba\(15,\s*23,\s*42,', 'rgba(19, 19, 20,' `
        -replace 'rgba\(11,\s*19,\s*38,', 'rgba(19, 19, 20,' `
        -replace '#0b1326', '#131314' `
        -replace '#0B1326', '#131314' `
        -replace '#ffb59d', '#ffb97c' `
        -replace '#FFB59D', '#ffb97c' `
        -replace '#dae2fd', '#e5e2e3' `
        -replace '#DAE2FD', '#e5e2e3'
    
    if ($updated -ne $content) {
        Set-Content $f.FullName $updated -NoNewline
        Write-Output "Updated: $($f.Name)"
    }
}
