DECLARE @A AS DATETIME
DECLARE @B AS DATETIME
/*WHERE*/
SET @A = /* T0.[CreateDate] */ '[%0]'
SET @B = /* T0.[CreateDate] */ '[%1]'
SELECT 
		T0.DocNum 'outgoing_no',
		T0.CardCode 'vendor_code',
		T0.CardName 'vendor_name',
        T0.CreateDate 'create_date',
		T0.DocDate 'posting_date',
		T0.DocTotal 'total_outgoing',
		T0.U_MIS_Signature1 'user_code',
		T2.DocNum 'invoice_no',
		T2.numAtCard 'vendor_ref',
		T2.[DocDate] 'invoice_date',
		T1.SumApplied 'invoice_amount',
		T1.WtAppld 'wtax_amount',
		T2.[PaidToDate], 
		T3.Currency,
T2.Comments
FROM 
	OVPM T0 
	LEFT JOIN VPM2 T1 ON T0.DocEntry = T1.DocNum
	LEFT JOIN OPCH T2 ON t1.DocEntry = T2.DocEntry
	LEFT JOIN OCRD T3 ON T0.CardCode = T3.CardCode
WHERE
T0.[CreateDate] >= @A AND T0.[CreateDate] <= @B
AND T1.WtAppld > 0
For Browse

/* SELECT FROM [dbo].[OVPM] T0*/