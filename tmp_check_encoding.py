from pathlib import Path
p = Path('manage_ns_conseil_20260807_151056.sql')
data = p.read_bytes()
print('size', len(data))
for enc in ['utf-8', 'utf-8-sig', 'cp1252', 'latin-1']:
    try:
        text = data.decode(enc)
        print(enc, 'decode ok', 'non_ascii_count', sum(1 for c in text if ord(c) > 127))
        bad = [c for c in text if ord(c) > 127][:20]
        print('sample', bad)
    except Exception as e:
        print(enc, 'decode error', e)
