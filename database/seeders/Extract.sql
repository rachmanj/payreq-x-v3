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
T0.BaseRef 'doc_num', 
CASE T1.TransType
	WHEN  '18' THEN 'AP Invoice'
	WHEN  '19' THEN 'AP Credit Memo' 
	END AS 'doc_type',
T3.USER_CODE 'user_code'
FROM OJDT T0 
INNER JOIN JDT1 T1 ON T0.TransId = T1.TransId
LEFT JOIN OOCR T2 ON T1.ProfitCode = T2.OcrCode
LEFT JOIN OUSR T3 ON T0.UserSign = T3.USERID
WHERE 
T1.TransType = '18' AND
T0.CreateDate>= @A AND T0.CreateDate<= @B
ORDER BY 
T0.TransId DESC