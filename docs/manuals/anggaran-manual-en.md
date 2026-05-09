# RAB / Anggaran (budget)

In this application, **Anggaran** is the budget record behind **RAB** (planning documents). Staff create and submit RAB through **My PayReqs**; accountants and controllers use **Reports** for dashboards, consolidation, fund pool, and maintenance.

## My PayReqs — RAB (user)

### Opening the menu

- Top menu **My PayReqs** → **RAB**, or  
- Sidebar under PayReqs → **RAB** (same route).

You need permission **akses_anggarans**. If **RAB** is missing from the menu, ask an administrator to grant that permission.

### List and drafts

The **index** lists your RAB/Anggaran records. From here you open **create** or an existing draft/submitted record depending on status and policy.

### Create a new RAB

1. Open **RAB** → **Create** (`/user-payreqs/anggarans/create`).  
2. Fill **nomor**, **rab_no**, **description**, **amount**, and related fields. Choose **RAB type** (**periode**, **event**, or **buc**).  
   - For **periode**, set **Periode anggaran** from the active list for your project.  
   - For **event** or **buc**, set **start date** and **end date** as required by the form.  
3. Optional **budget lines**: add detail rows (**account**, **description**, **amount**) when the screen provides line items.  
4. Attach a file if required by your workflow.  
5. Submit using the form actions for **save as draft** versus **submit** (buttons map to `button_type`: draft keeps the record editable; submit sends it into approval when `create_submit` / `edit_submit` is used successfully).

Successful **submit** creates an approval plan for type **`rab`** and sets status to **submitted** when approval setup succeeds.

### Edit and detail

- **Edit** opens the record when your user is allowed (**editThroughPayreq** policy on that Anggaran).  
- **Show** displays the record and linked payreq data (`payreqs_data`).  
You can remove a single budget detail line via the **detail destroy** route when the UI exposes it.

## Approvals menu — RAB

Approvers open **Approvals** in the navbar (or sidebar) → **RAB** to process submitted RAB requests (`approvals.request.anggarans` routes). Your organization’s approval stages apply after submission.

## Reports — RAB related (accounting / control)

Open **Reports** from **My PayReqs** when you have **akses_reports**, or use your assigned Reports entry point. On the Reports index page, under **RAB Related**, links include (each may require its own permission):

| Report name              | Typical permission       |
|-------------------------|---------------------------|
| **Periode RAB**          | **akses_periode_anggaran** |
| **RAB Dashboard**        | **akses_report_rab**       |
| **RAB Consolidated**     | **akses_report_rab**       |
| **RAB Fund pool**        | **recalculate_release**    |
| **RAB List**             | **akses_report_rab**       |

Use **Periode RAB** to maintain active periods. Use **RAB List** for search, bulk updates, **recalculate**, and inactive views as provided by your role. **Fund pool** actions mark amounts pooled or released where your permissions allow.

## Payreq linkage

Payreqs can reference Anggaran / budget depending on configuration (for example bulk activate/deactivate and budget link modes). Exact field labels appear on Payreq creation and realization screens tied to **anggarans** listing.
