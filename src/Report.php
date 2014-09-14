<?php
namespace Fcosrno\SendGridReport;
class Report {
  /**
   * Modules defined in SendGrid Web API
   * @var array
   */
  static $modules = array('spamreports','blocks','bounces','invalidemails','unsubscribes');
  /**
   * Actions defined in SendGrid Web API
   * @var array
   */
  static $actions = array('get','delete','count','add');
  /**
   * Parameters defined in SendGrid Web API
   * @var array
   */
  static $parameters = array('date','days','limit','offset','type','email');
  /**
   * Convert snake_case parameters to PHP-valid camelCase equivalent
   * @var array
   */
  static $parameters_underscored = array('start_date'=>'startDate','end_date'=>'endDate','delete_all'=>'deleteAll'); // to comply with PSR, methods shouldn't have underscores
  /**
   * Other vars
   * @var string
   */
  public $action, $module, $format;

/**
 * Initialize default values
 */
  public function __construct() {
    $this->format = 'json';
    $this->action = 'get';
  }

  /**
   * Setter for actions, modules and parameters.
   *
   * There's a lot of meta programming here that makes things look like magic. The setters
   * for modules, actions and parameters are set from chainable methods. So if you link spamreports(),
   * and delete() to the method chain in the object you are setting the module as 'spamreports' and
   * the action as "delete".
   *
   * For parameters, the method argument will pass through as the variable's value. For instance,
   * startDate('2014-01-01') will set $this->start_date to '2014-01-01'.
   *
   * Example:
   * 
   * $report->spamreports()->date()->days(1)->startDate('2014-01-01')->email('foo@bar.com');
   *
   * Sets the following:
   * $this->module = 'spamreports';
   * $this->action = 'get'; //default
   * $this->date = 1;
   * $this->days = 1;
   * $this->start_date = '2014-01-01';
   * $this->email = 'foo@bar.com';
   * 
   * 
   * @param  string $method Any of the methods in $this->modules
   * @param  string $args
   * @return this
   */
  public function __call($method, $args=null) {
    // set action (defaults to get)
    if(in_array($method,self::$actions))$this->action = $method;
    // set module
    if(in_array($method,self::$modules))$this->module = $method;
    // set parameter
    $parameters = array_merge(self::$parameters,array_values(self::$parameters_underscored));
    if(in_array($method,$parameters)){
      $method = $this->addUnderscore($method);
      $args = array_shift($args);
      if(empty($args)) $args = 1;
      $this->{$method} = $args;
    }
    return $this;
   }
  /**
   * Defines URL for SendGrid Web API
   * @param  string $url Overrides base URL in case endpoint changes in the future.
   * @return string
   */
  public function getUrl($url=null)
  {
    if(!isset($url)) $url = 'https://api.sendgrid.com/api/'.$this->module.'.'.$this->action.'.'.$this->format;
    return $url;
  }

  /**
   * Converts parameter to snake_case from camelCase. ie, startDate() becomes start_date
   * @param string $method
   */
  private function addUnderscore($method)
  {
      if(in_array($method,array_values(self::$parameters_underscored))){
        $method = str_replace(array_values(self::$parameters_underscored),array_keys(self::$parameters_underscored),$method);
      }
      return $method;
  }
  /**
   * Creates API valid array from list of parameters
   *
   * This is based on a similar function found in the official SendGrid lib:
   * https://github.com/sendgrid/sendgrid-php/blob/master/lib/SendGrid/Email.php
   * 
   * @return array
   */
  public function toWebFormat() {
    $web = array();
    $parameters = array_merge(self::$parameters,array_keys(self::$parameters_underscored));
    foreach($parameters as $parameter){
      if(isset($this->{$parameter}))$web[$parameter]=$this->{$parameter};
    }
    return $web;
  }
}