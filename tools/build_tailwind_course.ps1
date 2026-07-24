$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$inputFile = Join-Path $root 'assets\css\tailwind-course.input.css'
$outputFile = Join-Path $root 'assets\css\tailwind-course.css'
$binaryPath = Join-Path $env:TEMP 'tailwindcss-windows-x64.exe'
$downloadUrl = 'https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-windows-x64.exe'

if (-not (Test-Path $inputFile)) {
    throw "Input file not found: $inputFile"
}

if (-not (Test-Path $binaryPath)) {
    Write-Host 'Downloading Tailwind standalone CLI...'
    $ProgressPreference = 'SilentlyContinue'
    Invoke-WebRequest -Uri $downloadUrl -OutFile $binaryPath
}

Write-Host 'Building assets/css/tailwind-course.css...'
& $binaryPath -i $inputFile -o $outputFile --minify --cwd $root

if ($LASTEXITCODE -ne 0) {
    throw "Tailwind build failed with exit code $LASTEXITCODE"
}

Write-Host 'Build complete.'
