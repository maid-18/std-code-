<?php
require_once __DIR__ . '/includes/error_page.php';

renderErrorPage(404, 'الصفحة غير موجودة', 'الرابط الذي حاولت الوصول إليه غير موجود أو تم نقله.', '/', 'العودة للرئيسية');
