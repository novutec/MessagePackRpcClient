Novutec MessagePackRpcClient
============================

Implementation of a MessagePackRpcClient.

Copyright (c) 2007 - 2013 Novutec Inc. (http://www.novutec.com)
Licensed under the Apache License, Version 2.0 (the "License").

Installation
------------

Installing from source: `git clone git://github.com/novutec/MessagePackRpcClient.git` or [download the latest release](https://github.com/novutec/MessagePackRpcClient/zipball/master)

Move the source code to your preferred project folder.

Usage
-----

* Include Api.php
```
require_once 'MessagePackRpcClient/Api.php';
```

* Create Api() object
```
$Api = new Novutec\MessagePackRpcClient\Api('tcp://yourhost.com:port');
```

* If you want to send something just call the method you need
```
$result = $Parser->your_method($your_parameter1, $your_parameter2);
```

ChangeLog
---------
See ChangeLog at https://github.com/novutec/MessagePackRpcClient/wiki/ChangeLog

Issues
------
Please report any issues via https://github.com/novutec/MessagePackRpcClient/issues

LICENSE and COPYRIGHT
-----------------------
Copyright (c) 2007 - 2013 Novutec Inc. (http://www.novutec.com)

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.