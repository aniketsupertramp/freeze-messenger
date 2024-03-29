<?php
/**
 * xml2array Class
 * Converts XML Data to an array representation using the PHP5 DOMDocument.
 *
 * Origin:
 * @source http://www.phpclasses.org/package/5065-PHP-Parse-XML-documents-into-arrays.html
 * @license http://www.opensource.org/licenses/gpl-license.html
 * @author Vineet Sethi
 * Note: If you are the creator of this package, and do not feel like you have been properly attributed, please contact me at josephtparsons@gmail.com.
 *
 *
 * Modifications:
 * @license http://www.opensource.org/licenses/gpl-license.html
 * @author Joseph T. Parsons <josephtparsons@gmail.com>
 * -- Force subarrays on all but root element.
 * -- Use camelCase for properties and functions.
 * -- Replace htmlentitydecode with private function "decodeEntities"
 * -- Use more spaces and less new lines
 * -- Get rid of empty XML check
 * -- Throw exception instead of echoing for invalid XML
 */

class Xml2Array {
  /**
   * XML Dom instance
   *
   * @var XML DOM Instance
   */
  private $xmlDom;


  /**
   * Array representing xml
   *
   * @var array
   */
  private $xmlArray;


  /**
   * XML data
   *
   * @var String
   */
  private $xml;


  /**
   * Construct
   *
   * @param string xml
   * @return void
   */
  public function __construct($xml = '') {
    $this->xml = $xml;
  }


  /**
   * Set the XML Data
   *
   * @param string xml
   * @return void
   */
  public function setXml($xml) {
    if (!empty($xml)) {
      $this->xml = $xml;
    }
  }

  /**
   * Change xml data-to-array
   *
   * @return Array
   */
  public function getAsArray() {
    if ($this->getDom() === false) {
      return false;
    }

    $this->xmlArray = array();
    $root_element = $this->xmlDom->firstChild;
    $this->xmlArray[$root_element->tagName] = $this->node2Array($root_element);

    return $this->xmlArray;
  }


  /**
   * Converts a node to an array
   *
   * @param DOMNode dom_element
   * @return array
   */
  private function node2Array($dom_element) {
    if ($dom_element->nodeType != XML_ELEMENT_NODE) {
      return false;
    }

    $children = $dom_element->childNodes;

    foreach ($children as $child) {
      if ($child->nodeType != XML_ELEMENT_NODE) {
          continue;
      }

      $prefix = ($child->prefix) ? $child->prefix . ':' : '';

      if (!is_array($result[$prefix . $child->nodeName])) {
        foreach ($children as $test_node) {
          if ($child->nodeName == $test_node->nodeName && !$child->isSameNode($test_node)) {
            break;
          }
        }
      }

      $result[$prefix . $child->nodeName][] = $this->node2Array($child);
    }

    if (!is_array($result)) {
      $result['#text'] = $this->decodeEntities($dom_element->nodeValue);
    }

    if ($dom_element->hasAttributes()) {
      foreach ($dom_element->attributes as $attrib) {
        $prefix = ($attrib->prefix) ? $attrib->prefix . ':' : '';
        $result["@" . $prefix . $attrib->nodeName] = $attrib->nodeValue;
      }
    }

    return $result;
  }


  /**
   * Decode entities
   * @author Joseph T. Parsons <josephtparsons@gmail.com>
   */
  private function decodeEntities($value) {
    $value = str_replace(array('&amp', '&lt;', '&gt;', '&quot;', '&apos;'),
      array('&', '<', '>', '"', '\''),
      $value);

    return $value;
  }


  /**
   * Generated XML Dom
   *
   */
  private function getDom() {
    $this->xmlDom = @DOMDocument::loadXML($this->xml);

    if ($this->xmlDom) {
      return $this->xmlDom;
    }
    else {
      throw new Exception('Invalid XML Data');
    }
  }
}
?>