# phmLabs::Init

This library is a very lightweight dependency injection container. The special thing about `Init` is that it uses names parameters and fits perfectly with yaml files this way.

````php
class MyClass {
  public function __construct($firstParam, $secondParam = null) {
    // ...
  }
}

$options = [
    'class' => 'MyClass',
    'call' => [
      '__construct' => ['secondParam' => 'foo', 'firstParam' => 'bar']
    ]
]

$myClass = Init::initialize($options);
````