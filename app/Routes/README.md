# Improve routing system performance

Create a unique file per subdomain or section for your project by adding a new file with the same name. 

Example: 

http://api.domain.com or http://www.domain.com/api/

./routes/api.php

http://admin.domain.com or http://www.domain.com/admin/

./routes/admin.php

http://crm.domain.com or http://www.domain.com/crm/

./routes/crm.php

Aeros will load only those routes that correspond to the current subdomain or first element from the URI
