# Upgrade from 0.4 to 0.5

We have removed the protected `LinkedIn::init` function. That means if you were using `IlluminateSessionStorage` you have
to make a minor adjustment to your code. 

```php
// 0.4
$linkedIn=new Happyr\LinkedIn\LinkedIn('app_id', 'app_secret');

// 0.5
$linkedIn=new Happyr\LinkedIn\LinkedIn('app_id', 'app_secret');
$linkedIn->setStorage(new IlluminateSessionStorage());
```

If you don't know about `IlluminateSessionStorage` you are probably good ignoring this. 