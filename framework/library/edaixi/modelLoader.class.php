<?php
namespace Edx\Model;
/**
 * 模型管理器
 */
class ModelLoader {
    //存储加载的模型
    static protected $models = array();

    /**
     * 获取模型方法
     * @param string $model_name 模型名
     * @param var $params 给类的构造方法传递的参数
     */
    static public function get($model_name, $params = array())
    {
        $models = self::$models;
        $model = null;
        if (isset($models[$model_name])) {
            $model = $models[$model_name];
        } else {
            $model = self::load($model_name, $params);
        }
        return $model;
    }

    /**
     * 加载模型
     * @param string $model_name 模型名
     */
    static protected function load($model_name, $params)
    {
        $model_file = IA_ROOT . '/framework/model/' . $model_name . '.class.php';
        if (!is_file($model_file))
        {
            trigger_error("The model $model_name does not exist.", E_USER_ERROR);
        }
        $model_class = '\\Edx\\Model\\' . $model_name;
        require_once $model_file;
        if (empty($params)) {
            self::$models[$model_name] = new $model_class();
        } else {
            self::$models[$model_name] = new $model_class($params);
        }
        return self::$models[$model_name];
    }
}
