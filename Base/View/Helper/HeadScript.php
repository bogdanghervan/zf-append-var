<?php
/**
 * Helper that extends HeadScript to make it easier to pass variables to JavaScript.
 *
 * @category Lk
 * @package Lk_View
 * @subpackage Helper
 * @author Bogdan Ghervan <bogdan.ghervan@gmail.com>
 * @copyright Copyright (c) 2013 Bogdan Ghervan (http://ghervan.com/)
 **/
class Lk_View_Helper_HeadScript extends Zend_View_Helper_HeadScript
{
    /**#@+
     * Script type constants
     * @const string
     */
    const VARIABLE = 'VAR';
    /**#@-*/
    
    /**
     * Returns headScript object.
     *
     * Returns headScript helper object; optionally, allows specifying a script,
     * a script file or a variable to include.
     *
     * @param  string $mode Script or file or variable
     * @param  string|array $spec Script/url/variable as key-value pair
     * @param  string $placement Append, prepend, or set
     * @param  array $attrs Array of script attributes
     * @param  string $type Script type and/or array of script attributes
     * @return Zend_View_Helper_HeadScript
     */
    public function headScript($mode = Zend_View_Helper_HeadScript::FILE, $spec = null, $placement = 'APPEND', array $attrs = array(), $type = 'text/javascript')
    {
        if ((null !== $spec) && (is_string($spec) || is_array($spec))) {
            $action    = ucfirst(strtolower($mode));
            $placement = strtolower($placement);
            switch ($placement) {
                case 'set':
                case 'prepend':
                case 'append':
                    $action = $placement . $action;
                    break;
                default:
                    $action = 'append' . $action;
                    break;
            }
            if (is_string($spec)) {
                $this->$action($spec, $type, $attrs);
            } else {
                list($name, $value) = $spec;
                $this->$action($name, $value, $type, $attrs);
            }
        }

        return $this;
    }
    
    /**
     * Overloads method access.
     *
     * Allows the following method calls:
     * - appendFile($src, $type = 'text/javascript', $attrs = array())
     * - offsetSetFile($index, $src, $type = 'text/javascript', $attrs = array())
     * - prependFile($src, $type = 'text/javascript', $attrs = array())
     * - setFile($src, $type = 'text/javascript', $attrs = array())
     * - appendScript($script, $type = 'text/javascript', $attrs = array())
     * - offsetSetScript($index, $src, $type = 'text/javascript', $attrs = array())
     * - prependScript($script, $type = 'text/javascript', $attrs = array())
     * - setScript($script, $type = 'text/javascript', $attrs = array())
     * - appendVar($name, $value, $type = 'text/javascript', $attrs = array())
     * - offsetSetVar($index, $name, $value, $type = 'text/javascript', $attrs = array())
     * - prependVar($name, $value, $type = 'text/javascript', $attrs = array())
     * - setVar($name, $value, $type = 'text/javascript', $attrs = array())
     *
     * @param  string $method
     * @param  array $args
     * @return Base_View_Helper_HeadScript
     * @throws Baes_View_Exception if too few arguments or invalid method
     */
    public function __call($method, $args)
    {
        if (preg_match('/^(?P<action>set|(ap|pre)pend|offsetSet)Var$/', $method, $matches)) {
            if (count($args) < 2) {
                require_once 'Zend/View/Exception.php';
                $e = new Zend_View_Exception(sprintf('Method "%s" requires at least two arguments', $method));
                $e->setView($this->view);
                throw $e;
            }

            $action = $matches['action'];

            if ('offsetSet' == $action) {
                $index = array_shift($args);
                if (count($args) < 2) {
                    require_once 'Zend/View/Exception.php';
                    $e = new Zend_View_Exception(sprintf('Method "%s" requires at least three arguments, an index, a variable name and its value', $method));
                    $e->setView($this->view);
                    throw $e;
                }
            }

            $name  = $args[0];
            $value = $args[1];
            $type  = isset($args[2]) ? (string) $args[2] : 'text/javascript';
            $attrs = isset($args[3]) ? (array) $args[3] : array();
            
            $item = $this->createVar($name, $value, $type, $attrs);
            if ('offsetSet' == $action) {
                $this->offsetSet($index, $item);
            } else {
                $this->$action($item);
            }

            return $this;
        }

        return parent::__call($method, $args);
    }

    /**
     * Retrieves string representation.
     *
     * @param  string|int $indent
     * @return string
     */
    public function toString($indent = null)
    {
        $indent = (null !== $indent)
                ? $this->getWhitespace($indent)
                : $this->getIndent();

        if ($this->view) {
            $useCdata = $this->view->doctype()->isXhtml() ? true : false;
        } else {
            $useCdata = $this->useCdata ? true : false;
        }
        $escapeStart = ($useCdata) ? '//<![CDATA[' : '//<!--';
        $escapeEnd   = ($useCdata) ? '//]]>'       : '//-->';

        // Collect variables and bundle them together in a single script tag at the beginning
        $vars = array();
        $items = array();
        $this->getContainer()->ksort();
        foreach ($this as $item) {
            if (!$this->_isValid($item)) {
                continue;
            }
            
            // If it's a var that has a name
            if (isset($item->name)) {
                $disableEncoding = array_key_exists('disableEncoding', $item->attributes) ?
                    (bool) $item->attributes['disableEncoding'] : false;
                
                if ($disableEncoding) {
                    $vars[] = sprintf('%s = %s', $item->name, $item->source);
                } else {
                    $vars[] = sprintf('%s = %s', $item->name, Zend_Json::encode($item->source));
                }
            } else {
                $items[] = $this->itemToString($item, $indent, $escapeStart, $escapeEnd);
            }
        }

        $script = sprintf('var %s;', implode(', ', $vars));
        $item = $this->createData('text/javascript', array(), $script);
        
        $return = $this->itemToString($item, $indent, $escapeStart, $escapeEnd)
            . $this->getSeparator() . implode($this->getSeparator(), $items);
        
        return $return;
    }
    
    /**
     * Create data item for a variable.
     *
     * @param  string $name
     * @param  mixed $value
     * @param  string $type
     * @param  array $attributes
     * @return stdClass
     */
    public function createVar($name, $value, $type, array $attributes)
    {
        $data             = new stdClass();
        $data->type       = $type;
        $data->attributes = $attributes;
        $data->source     = $value;
        $data->name       = $name;
        return $data;
    }
}
