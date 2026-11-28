<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Find your next great experience with khanEvents.">
    <title>@yield('title', 'Events') · khanEvents</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{font-family:'Segoe UI',Arial,sans-serif;color:#1f2937;background:#f3f4f6;font-synthesis:none;text-rendering:optimizeLegibility}*{box-sizing:border-box}body{margin:0}a{color:inherit;text-decoration:none}.wrap{width:min(1000px,calc(100% - 48px));margin:auto}.nav{height:auto;min-height:78px;display:flex;align-items:center;justify-content:space-between;background:#4f46e5;color:#fff;padding:20px 24px;border-radius:8px;margin:24px auto 32px}.brand{font:700 24px 'Segoe UI',Arial,sans-serif;letter-spacing:0}.brand-mark{color:#fff}.nav-link{font-size:14px;color:#fff}.eyebrow{text-transform:uppercase;letter-spacing:1px;font-size:12px;font-weight:700;color:#4f46e5}.hero{padding:12px 0 32px;max-width:700px}.hero h1,.detail h1{font:700 clamp(36px,5vw,54px)/1.08 'Segoe UI',Arial,sans-serif;letter-spacing:-1.5px;margin:12px 0}.hero p,.detail-intro{color:#6b7280;font-size:16px;line-height:1.6;max-width:540px;margin:0}.section-heading{display:flex;justify-content:space-between;align-items:center;margin:5px 0 16px}.section-heading h2{font:700 22px 'Segoe UI',Arial,sans-serif;margin:0}.count{font-size:14px;color:#6b7280}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;padding-bottom:48px}.card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;transition:transform .18s,box-shadow .18s}.card:hover{transform:translateY(-2px);box-shadow:0 6px 16px #00000012}.card-art{height:12px;padding:0;background:#4f46e5!important;position:relative;overflow:hidden}.card-art:after{display:none}.badge{display:none}.card-body{padding:20px}.date{font-size:14px;color:#6b7280;font-weight:400}.card h3{font:700 20px 'Segoe UI',Arial,sans-serif;margin:10px 0;color:#1f2937;letter-spacing:0}.venue{color:#6b7280;font-size:14px;margin:12px 0}.card-bottom{border:0;margin-top:8px;padding-top:8px;display:flex;justify-content:space-between;align-items:center;font-size:16px}.price{font-weight:700;color:#15803d}.arrow{color:#4f46e5;font-size:18px}.footer{border-top:1px solid #e5e7eb;padding:24px 0;color:#6b7280;font-size:12px}.detail{padding:28px 0 70px;max-width:800px}.back{display:inline-block;color:#4f46e5;font-size:14px;margin-bottom:30px}.detail-panel{margin-top:28px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:24px;display:grid;grid-template-columns:1fr 1fr;gap:24px}.detail-label{color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:1px}.detail-value{font-weight:600;margin-top:7px}.detail-price{font:700 24px 'Segoe UI',Arial,sans-serif;color:#15803d}.description{color:#6b7280;line-height:1.7;margin-top:24px}.cta{display:inline-flex;margin-top:22px;background:#4f46e5;color:#fff;padding:12px 20px;border-radius:6px;font-size:14px;font-weight:600}.cta:hover{background:#4338ca}@media(max-width:600px){.wrap{width:calc(100% - 32px)}.nav{margin:16px auto 24px;padding:18px}.grid{grid-template-columns:1fr}.hero{padding:8px 0 28px}}
    </style>
    <style>
        .event-form{max-width:720px;margin:0 auto 48px 0;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:24px;display:grid;grid-template-columns:1fr 1fr;gap:18px}.form-field{display:flex;flex-direction:column;gap:7px}.form-field.full{grid-column:1/-1}.form-field label{font-weight:600;font-size:14px;color:#374151}.form-field input,.form-field textarea{font:inherit;padding:11px 12px;border:1px solid #d1d5db;border-radius:6px;width:100%}.form-field textarea{min-height:110px;resize:vertical}.form-errors{grid-column:1/-1;background:#fee2e2;color:#991b1b;padding:14px 18px;border-radius:6px}.submitted-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:24px;margin-bottom:48px}.submitted-card .full{grid-column:1/-1}@media(max-width:600px){.event-form{margin:0 auto 48px;grid-template-columns:1fr;padding:18px}.form-field.full,.form-errors{grid-column:auto}.submitted-card .full{grid-column:auto}}
    </style>
</head>
<body>
    <header class="wrap nav">
        <a class="brand" href="{{ route('events.index') }}">khan<span class="brand-mark">Events</span></a>
        <nav style="display:flex;gap:20px;align-items:center">
            <a class="nav-link" href="{{ route('events.index') }}">Discover events</a>
            <a class="nav-link" href="{{ route('events.create') }}">Add event <span aria-hidden="true">＋</span></a>
        </nav>
    </header>
    <main class="wrap">@yield('content')</main>
    <footer class="footer"><div class="wrap">Made for gathering. © {{ date('Y') }} khanEvents</div></footer>
</body>
</html>
