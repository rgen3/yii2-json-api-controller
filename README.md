# yii2-json-api-controller
Provides a possibility to work with json requests with friendly request validation
using native yii2 model rules

# Installation

```
composer require rgen3/yii2-json-api-controller
```

Usage example
---

Create a request folder and a request class
```php
<?php

namespace app\requests;

use yii\base\Model;

class MyRequest extends Model 
{
    public $exampleVar;

    public function rules()
    {
        return [
            ['exampleVar', 'required'],
        ];
    }
}

```

Create a controller

```php
<?php

use rgen3\controller\json\BaseController;
use app\requests\MyRequest;


class SiteController extends BaseController
{
    // Note sending data to autoload for the request you should use key named `data` as wrapper
    // for you json request
    //
    // i.e. here as input datum you have to use
    // "
    //  {"data" : {"exampleVar" : "myValue" }}
    // "
    public function actionIndex(MyRequest $request)
    {
        // return any success response
        return $this->success([
            'theValue' => $request->exampleVar,
        ]);
    }

    // Also you can any typehints to provide a native yii2 behaviour
    public function actionError(int $anyVar = 0)
    {
        // $anyVar will contains your value or zero
        return $this->error(['Error data']);
    }

    // Also you can leave input params empty
    public function actionThrows()
    {
        // Throw any exception you want
        throw new yii\web\BadRequestHttpException('The exception');
    }
    
    public function actionAnotherException()
    {
        throw new \Exception('Not http exception');
    }
}

```

When you call `actionIndex` you'll get 
```json
    {
      "request-id": null,
      "status-code": 200,
      "status-text": "OK",
      "status": "success",
      "data": {
        "theValue": "your value"
      }
    }
```

note that you should generate request-id and set it to `X-Request-Id` http header

Calling `actionError` you'll get

```json
{
  "request-id": null,
  "status-code": 200,
  "status-text": "OK",
  "status": "error",
  "data": [
    "Error class"
  ]
}
```

Executing `actionThrows` returns 
```json
{
  "request-id": null,
  "status-code": 400,
  "status-text": "Bad Request",
  "status": "error",
  "data": [
    
  ]
}
```

If you have unhandled exception as in `actionAnotherException` you'll get
```json
{
  "request-id": null,
  "status-code": 500,
  "status-text": "Internal Server Error",
  "status": "error",
  "data": [
    "Not http exception"
  ]
}
```
