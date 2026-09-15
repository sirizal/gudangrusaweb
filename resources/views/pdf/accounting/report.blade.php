<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #111827; }
        .subtitle { font-size: 11px; color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f4f6; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; padding: 6px 8px; border-bottom: 1px solid #d1d5db; }
        td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .group { font-weight: 700; background: #f9fafb; }
        .total td { font-weight: 700; border-top: 1px solid #9ca3af; border-bottom: none; background: #f9fafb; }
        .balanced { margin-top: 12px; font-weight: 700; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    @if (! empty($subtitle))
        <div class="subtitle">{{ $subtitle }}</div>
    @endif
    <table>
        @if (! empty($headers))
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            @forelse ($rows as $row)
                <tr @class(['total' => ($row['total'] ?? false)])>
                    @foreach ($row['cells'] as $cell)
                        @if ($cell['numeric'] ?? false)
                            <td class="num">{{ $cell['value'] }}</td>
                        @else
                            <td>{{ $cell['value'] }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr><td>No data.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if (! empty($note))
        <div class="balanced">{!! $note !!}</div>
    @endif
</body>
</html>