<?
// Cream: Newsletter


$newsletterBody = '
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:650px;border:1px solid #ccc;margin:30px 0;"><tr> <td style="width:40px"></td> <td style="padding-top:20px" align="center">  <img src="https://www.newsjunction.net/data/logos/bizprout_logo.png" width="150" /><br>  <div style="font-size:20px;font-family:Helvetica,Arial,sans-serif;">Bizprout Update</div>  <div style="font-size:13px;font-family:Helvetica,Arial,sans-serif;">Mar 09, 2021 | Publisher: Bizprout</div> </td> <td style="width:40px"></td></tr><tr> <td></td> <td style="padding-top:40px;padding-bottom:20px;font-family:Georgia,serif;font-size:16px;line-height:1.5em;color:#000000" align="left">  <div style="padding-bottom:40px">   <div style="padding-bottom:10px;font-size:14pt;"><a href="https://www.newsjunction.net/view/2268/Webinar-on-New-Labour-Law-Codes" target="_blank"><strong>Webinar on New Labour Law Codes</strong></a></div>   <span style="font-weight:400"><p><strong>Decoding new labour codes<br /></strong>Changes, impact, action plan<br /><br />New Labour Codes are set to roll out in the year 2021. The new codes are designed to be business friendly while being stringent on non-compliance.</p>
<p><strong>Who should attend this webinar?</strong></p>
<ul>
<li>CxOs of companies who employ people either permanent, temporary or on contract</li>
<li>Founders and CxOs of Start-ups</li>
<li>HR or Compliance heads of established companies</li>
</ul>
<p>&nbsp;</p>
<p>Understand what it takes to be Labour Law Compliant under the new codes. Register Now.</p>
<table style="border-collapse: collapse;" border="1">
<tbody>
<tr>
<td width="93">
<p>Topic</p>
</td>
<td width="295">
<p><strong>Decoding new labour codes<br /></strong>Changes, impact, action plan<br /><br /></p>
</td>
</tr>
<tr>
<td width="93">
<p>Date</p>
</td>
<td width="295">
<p>18-12-2020</p>
</td>
</tr>
<tr>
<td width="93">
<p>Time</p>
</td>
<td width="295">
<p>3 &ndash; 4 PM</p>
</td>
</tr>
<tr>
<td width="93">
<p>Register</p>
</td>
<td width="295">
<p><a class="button3" href="https://us02web.zoom.us/webinar/register/WN_yMChR7eERB2_xjAmsDONjg" target="_blank" rel="noopener">Register on Zoom</a></p>
</td>
</tr>
</tbody>
</table></span>   <br clear="all">  </div> </td> <td></td></tr><tr> <td></td> <td align="center">  Powered by <a href="https://www.newsjunction.net/"><img src="https://www.newsjunction.net/grfx/logo.png" width="100" align="middle" style="padding-bottom:10px"></a><br><br> </td> <td></td></tr></table>';


$newsletterBody = str_replace('"', '\"', $newsletterBody);
$newsletterBody = str_replace(array("\r\n", "\n\r", "\n", "\r"), "", $newsletterBody);

echo $newsletterBody;


 $curl = curl_init();
 curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.sendgrid.com/v3/marketing/singlesends",
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => "{\"name\":\"Sent from News Junction\",\"send_to\":{\"list_ids\":[\"f59d9bed-9350-4153-b505-a372d7a45b6e\"]},\"email_config\":{\"sender_id\":1425401,\"suppression_group_id\":15894,\"subject\":\"Test\",\"html_content\":\"$newsletterBody\"}}",
  CURLOPT_HTTPHEADER => array(
   "authorization: Bearer YOUR_SENDGRID_API_KEY.WMKkvof6Kn3e043_4Foi4bu3_swVh7bxuxO0iHYJXxY",
   "content-type: application/json"
  ),
 ));
 $response = curl_exec($curl);
 $err = curl_error($curl);
 if ($err) {
 } else {
  echo $response;
 }
 curl_close($curl);