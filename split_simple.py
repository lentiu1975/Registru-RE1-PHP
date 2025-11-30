# coding: utf-8
import os
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

print("Impartire migrate_simple.sql in bucati mici...\n")

# Citeste fisierul SQL mare
with open('migrate_simple.sql', 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Gaseste liniile de header
header_lines = []
data_lines = []

for line in lines:
    if line.startswith('--') or line.startswith('SET ') or line.startswith('DELETE '):
        header_lines.append(line)
    elif line.strip() and not line.startswith('--'):
        data_lines.append(line)

print(f"Total linii SQL de date: {len(data_lines)}")

# Imparte in fisiere de cate 500 linii
lines_per_file = 500
num_files = (len(data_lines) + lines_per_file - 1) // lines_per_file

print(f"Voi crea {num_files} fisiere SQL\n")

# Creeaza folder pentru fisiere
if not os.path.exists('sql_simple'):
    os.makedirs('sql_simple')

for i in range(num_files):
    start = i * lines_per_file
    end = min((i + 1) * lines_per_file, len(data_lines))

    filename = f'sql_simple/part_{i+1:02d}_of_{num_files:02d}.sql'

    with open(filename, 'w', encoding='utf-8') as f:
        # Header doar in primul fisier
        if i == 0:
            f.writelines(header_lines)
            f.write('\n')
        else:
            f.write('-- Migrare PARTE {}/{}\n'.format(i+1, num_files))
            f.write('SET NAMES utf8mb4;\n')
            f.write('SET CHARACTER SET utf8mb4;\n\n')

        # Date
        f.writelines(data_lines[start:end])

        f.write('\n-- DONE PART {}/{}\n'.format(i+1, num_files))

    print(f"✓ Creat {filename} ({end - start} linii)")

print(f"\nGATA! {num_files} fisiere create in folder 'sql_simple/'")
