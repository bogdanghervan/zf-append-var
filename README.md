# zf-append-var
View helper that makes it easy to pass any type of data to JavaScript in Zend Framework 1 using a beautiful syntax.

## Usage

### Before

This is how passing content to JavaScript's scope looks like in a typical Zend Framework 1 project:
```PHP
$this->view->headScript()
     ->appendScript('var baseUrl="' . Zend_Json::encode($baseUrl) . '";')
     ->appendScript('var errorMessage="User not found"';);
```
Doing it like this would produce the following markup:
```HTML
<script type="text/javascript">
    //<!--
var baseUrl="http:\/\/www.example.com\/"; //-->
</script>
<script type="text/javascript">
    //<!--
var errorMessage="User not found"; //-->
</script>
```
This gets icky very fast when you're passing a lot of data to JS.

### After

zf-append-var provides a much simpler and cleaner way to pass data to JavaScript:
```PHP
$this->view->headScript()
     ->setVar('baseUrl', $baseUrl)
     ->appendVar('errorMessage', 'User not found')
     ->prependVar('bookingIds', array(9006, 9007))
     ->offsetSetVar(2, 'isLoggedIn', false);
```
The resulting code is more compact too:
```HTML
<script type="text/javascript">
    //<!--
var bookingIds = [9006,9007], baseUrl = "http:\/\/www.example.com\/", isLoggedIn = false;    //-->
</script>
```

All the methods that you'd expect in a container-style view helper (like `Zend_View_Helper_HeadScript` or `Zend_View_Helper_HeadStyle`) are supported.
In most cases you will only need `appendVar`, but you can combine the methods below
to control the order JavaScript variables show up in the outputted code.
#### appendVar
Appends a variable (will show up last in the outputted code).
```PHP
public function appendVar($name, $value, $type = 'text/javascript', $attrs = array());
```

#### offsetSetVar
Inserts a variable at the specified `$index` position.
```PHP
public function offsetSetVar($index, $name, $value, $type = 'text/javascript', $attrs = array());
```

#### prependVar
Prepends a variable (will show up first in the ouputted code).
```PHP
public function prependVar($name, $value, $type = 'text/javascript', $attrs = array());
```

#### setVar
Passes a variable to JavaScript while overriding any previously passed data.
```PHP
public function setVar($name, $value, $type = 'text/javascript', $attrs = array());
```

## Installation
* [Download](https://github.com/bogdanghervan/zf-append-var/archive/master.zip) the code.
* Copy the `Base` directory inside the downloaded archive to your `/library` folder in your Zend Framework 1 installation.
* Make sure the `Base` namespace is autoloaded by adding this directive to your `application.ini` file(s):
```ini
autoloaderNamespaces.base = "Base_"
```
* Now let's tell `Zend_View` how it could find our new helper. We could do it in the `Bootstrap.php` file like this:
```PHP
protected function _initView()
{
    $this->bootstrap('view');
    $view = $this->getResource('view');
    $view->addHelperPath('Base/View/Helper/', 'Base_View_Helper');	
}
```
... and we're done!

## License

New BSD License
