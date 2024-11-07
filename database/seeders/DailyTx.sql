/* SELECT FROM [dbo].[OJDT] T0*/
/* SELECT FROM [dbo].[JDT1] T1*/
DECLARE @A AS DATETIME
DECLARE @B AS DATETIME
/* WHERE */
SET @A = /* T0.CreateDate 'FromDate'  */ '[%0]'
SET @B = /* T0.CreateDate 'ToDate' */ '[%1]'
SELECT
T0.CreateDate 'create_date',
T0.RefDate 'posting_date',
T0.TransId 'tx_num',
T0.BaseRef 'doc_num', 
CASE T1.TransType
	WHEN  '-2' THEN 'Opening Balance'
	WHEN  '13' THEN 'AR Invoice' 
	WHEN  '14' THEN 'AR Credit Memo'
	WHEN  '203' THEN 'AR DP'
	WHEN  '15' THEN 'Material Issue'	
	WHEN  '16' THEN 'Material Return'
	WHEN  '18' THEN 'AP Invoice'
	WHEN  '19' THEN 'AP Credit Memo' 
	WHEN '204' THEN 'AP DP'
	WHEN  '20' THEN 'Goods Receipt PO' 
	WHEN  '202' THEN 'Production Order' 
	WHEN  '21' THEN 'Goods Return' 
	WHEN  '24' THEN 'Incoming Payments' 
	WHEN  '30' THEN 'Journal Entry'
	WHEN  '46' THEN 'Outgoing Payments' 
	WHEN  '59' THEN 'Goods Receipt'
	WHEN  '60' THEN 'Goods Issue'
	WHEN  '67' THEN 'InventoryTransfer'
	WHEN  '69' THEN 'Landed Costs' 
	WHEN '321' THEN 'Intenal Reconciliation'
	WHEN  '162' THEN 'Inventory Revaluation'
	END AS 'doc_type',
T1.Project 'project_code',
--T1.ProfitCode [Department],
T2.OcrName 'department',
T1.Account 'account', 
--T0.TransCurr,
T1.Debit 'debit',
T1.Credit 'credit', 
T1.FCDebit 'fc_debit', 
T1.FCCredit 'fc_credit',
T0.Memo 'remarks',
T3.USER_CODE 'user_code',
T3.U_NAME 'user_name'
FROM OJDT T0 
INNER JOIN JDT1 T1 ON T0.TransId = T1.TransId
LEFT JOIN OOCR T2 ON T1.ProfitCode = T2.OcrCode
LEFT JOIN OUSR T3 ON T0.UserSign = T3.USERID
WHERE 
T1.TransType IN ('18','46') AND
T0.CreateDate>= @A AND T0.CreateDate<= @B
ORDER BY 
T0.TransId DESC