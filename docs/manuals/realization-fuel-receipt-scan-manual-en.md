# Scan fuel receipts for realization (My PayReqs)

This feature uses AI (OpenRouter / vision model) to read **Pertamina SPBU fuel receipts** from photos and create **realization detail** lines faster. It is intended **only for fuel purchase receipts** (**Nota Pembelian Fuel**), not for general invoices or other expense types.

## Who can use it

Any user who can open **My PayReqs** and add realization details on a **draft** realization (same access as **Add Detail** on the realization details page). The server checks that you own the realization (or are **superadmin**).

## Prerequisites

- A **paid** payreq with a **draft** realization opened on **Add realization details** (`user-payreqs/realizations/{id}/add_details`).
- **`OPENROUTER_API_KEY`** configured on the server (administrator). Without it, scan requests return an error.
- Clear photos: one receipt per image works best; a single photo with **many receipts laid out** can return **multiple rows** after **Scan All**.

## Opening the realization details page

1. Go to **My PayReqs** in the top menu.
2. Open your payreq and start or continue **realization** until you reach **Add realization details** (table of detail lines, **Add Detail**, **Scan Fuel Receipts**, **Submit Realization**).

## Scan Fuel Receipts (bulk — recommended)

Use the yellow **Scan Fuel Receipts** button in the card header (subtitle: **Hanya Nota Pembelian Fuel**).

1. Click **Scan Fuel Receipts**.
2. In the modal, choose one or more **receipt images** (JPEG/PNG; mobile can use the camera).
3. Click **Scan All**. The system sends each image to the AI **one file at a time** and shows progress (**X / N**).
4. Review the table: one row per receipt found. Columns include **Description**, **Amount**, **Date**, **HM**, **Unit**, **Nopol**, **Qty**. Edit any cell before saving.
5. Remove bad rows with the trash icon on a row.
6. Click **Save All** to insert all valid rows into the realization details table.
7. Confirm totals and variance in the footer, then **Submit Realization** when ready.

If one image contains **several receipts** (e.g. a photo of many slips on a table), **Scan All** should add **one row per receipt** detected in that image.

## Add Detail without bulk scan

You can still add lines manually with **Add Detail** (description, amount, expense date, fleet fields). Per-line **Scan Receipt with AI** inside the Add/Edit modal may be **hidden** by configuration; when enabled, it fills the open form from a single receipt photo.

## Fields the AI tries to fill

| Field | Source on receipt |
|--------|-------------------|
| **Description** | e.g. `BBM Pertamax - SPBU 6476112`, or **Fuel Kendaraan** if grade/SPBU unreadable |
| **Amount** | Total amount (Rupiah) |
| **Expense date** | Transaction date on the slip |
| **HM** (`km_position`) | Handwritten or printed KM / odometer |
| **Unit No** | Handwritten code like **VA 057**, **VA 083** (two letters, space, three digits) |
| **Nopol** | Printed plate if present (ignored when **Not Entered**) |
| **Qty** | Liters |
| **Type** / **UOM** | Set to **fuel** / **liter** for SPBU receipts |

**Unit No** must exist in the equipment list (**Unit No** dropdown). If the AI reads **VA 057** but that unit is not in the list, pick the correct unit manually before saving.

## After saving scanned lines

- The details table refreshes automatically.
- **Submit Realization** stays disabled until at least one detail exists.
- Fleet rules still apply (expense date not in the future, HM monotonicity per unit, etc.). Fix validation errors on the review table or edit the row after save.

## Troubleshooting

- **Scan failed / OpenRouter not configured** — contact IT to set **`OPENROUTER_API_KEY`** and **`OPENROUTER_MODEL`** in `.env`.
- **Only one row from many receipts** — use a stronger vision model (e.g. **`google/gemini-2.5-pro`**) or photograph receipts separately; retake with better lighting.
- **Wrong unit** — correct **Unit** in the review table; handwriting must match a code in the equipment master.
- **HELP answers outdated** — administrator runs `php artisan help:reindex` after manual updates under `docs/manuals/`.
