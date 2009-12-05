<?php

/**
 * 独自のバリデーションルールを追加するbehavior プラグイン
 * 内部文字コードはデフォルトUTF-8（オプションで変更可能）
 * Behavior of some validation rules.
 * Internal encoding is UTF-8, can change it with parameter.
 *
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Yasushi Ichikawa. (http://d.hatena.ne.jp/cakephper/)
 * @link          http://d.hatena.ne.jp/cakephper/
 * @package       cakeplus
 * @subpackage    add_validation_rule
 * @version       0.03
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 *
 * =====利用方法=====
 * 各モデルファイルで、下記のように使う。app_modelにactsAsで指定しても可
 * In each model class or app_model, write code as follow.
 *		var $actsAs = array('Cakeplus.AddValidationRule');
 *
 * 内部文字コードを変更したい場合は、オプションで変更可能（デフォルトはUTF-8）
 * If you want to change encoding(UTF-8), write code as follow.
 *		var $actsAs = array('Cakeplus.AddValidationRule' => array('encoding' => 'EUC') );
 *
 *
 * 各モデルファイル内のバリデーションの書き方は下記を参考に。
 * Example: validation definition in a model.
 * 		var $validate = array(
 * 			'test' => array(
 *				"rule2" => array('rule' => array('maxLengthJP', 5),
 * 					'message' => '5文字以内です'
 * 				),
 *				"rule3" => array('rule' => array('minLengthJP', 2),
 * 					'message' => '2文字以上です'
 * 				),
 *				"rule4" => array('rule' => array('compare2fields', 'test_conf'),
 * 					'message' => '値が違います'
 * 				),
 * 				"rule5" => array('rule' => array('space_only'),
 * 					'message' => 'スペース以外も入力してください'
 * 				),
 *	 			"rule6" => array('rule' => array('katakana_only'),
 *					'message' => 'カタカナのみ入力してください'
 *	 			),
 *	 		),
 *	 	);
 *
 * Authコンポーネントでパスワードフィールドがハッシュ化されている場合は、compare2fieldsの第3配列にtrueを指定する
 * Using Auth component, If you want compare password and password confirm field,
 * set "true" in 3rd parameter of compare2fields validation, password_conf field is encrypted.
 *	 	var $validate = array(
 * 			'password' => array(
 *				"rule" => array('rule' => array('compare2fields', 'password_conf',true),
 * 					'message' => '値が違います'
 * 				),
 * 			),
 * 		);
 *
 *
 */
class AddValidationRuleBehavior extends ModelBehavior {

	function setup(&$model, $config = array()){

		//change encoding with parameter option.
		if( !empty( $config['encoding'] ) ){
			mb_internal_encoding($config['encoding']);
		}else{
			mb_internal_encoding("UTF-8");
		}
	}


	/**
	 * マルチバイト用バリデーション　文字数上限チェック
	 * check max length with Multibyte character.
	 *
	 * @param array &$model  model object, automatically set
	 * @param array $wordvalue  field value, automatically set
	 * @param int $length max length number
	 * @return boolean
	 */
	function maxLengthJP( &$model, $wordvalue, $length ) {
		$word = array_shift($wordvalue);
		return( mb_strlen( $word ) <= $length );
	}

	/**
	 * マルチバイト用バリデーション　文字数下限チェック
	 * check min length with Multibyte character.
	 *
	 * @param array &$model model object, automatically set
	 * @param array $wordvalue field value, automatically set
	 * @param int $length min length number
	 * @return boolean
	 */
	function minLengthJP( &$model, $wordvalue, $length ) {
		$word = array_shift($wordvalue);
		return( mb_strlen( $word ) >= $length );
	}


	/**
	 * フィールド値の比較
	 * emailとemail_confフィールドを比較する場合などに利用
	 * $compare_filedに比較したいフィールド名をセットする（必須）
	 * Compare 2 fields value. Example, email field and email_conf field.
	 * Set field name for comparison in $compare_filed
	 *
	 * authにtrueを指定すると、Authコンポーネントのパスワードフィールドを前提として
	 * 比較するpassword_confフィールドの値をハッシュ化する
	 * If set "true" in $auth, $compare_filed is encrypted with Security::hash.
	 *
	 * @param array &$model  model object, automatically set
	 * @param array $wordvalue  field value, automatically set
	 * @param string $compare_filed  set field name for comparison
	 * @param boolean $auth set true, $compare_filed is encrypted with Security::hash
	 * @return boolean
	 */
	function compare2fields( &$model, $wordvalue , $compare_filed , $auth = false ){

		$fieldname = key($wordvalue);
		if( $auth === true ){
			App::import('Component','Auth');
			return ( $model->data[$model->alias][$fieldname] === AuthComponent::password($model->data[$model->alias][ $compare_filed ]) );
		}else{
			return ( $model->data[$model->alias][$fieldname] === $model->data[$model->alias][ $compare_filed ] );
		}
	}



	/**
	 * 全角カタカナ以外が含まれていればエラーとするバリデーションチェック
	 * Japanese KATAKANA Validation
	 *
	 * @param array &$model
	 * @param array $wordvalue
	 * @return boolean
	 */
	function katakana_only( &$model, $wordvalue){

	    $value = array_shift($wordvalue);

	    return preg_match("/^[ァ-ヶー゛゜]*$/u", $value);

	}




	/**
	 * 全角、半角スペースのみであればエラーとするバリデーションチェック
	 * Japanese Space only validation
	 *
	 * @param array &$model
	 * @param array $wordvalue
	 * @return boolean
	 */
	function space_only( &$model, $wordvalue){

	    $value = array_shift($wordvalue);

	    if( mb_ereg_match("^(\s|　)+$", $value) ){

		    return false;
	    }else{
	        return true;
	    }
	}


	/**
	 * only Allow 0-9, a-z , A-Z
	 * check it including Multibyte characters.
	 *
	 * @param array ref &$model
	 * @param array $wordvalue
	 * @return boolean
	 */
	function alpha_number( &$model, $wordvalue ){
		$value = array_shift($wordvalue);
		return preg_match( "/^[a-zA-Z0-9]*$/", $value );

	}

}

?>
