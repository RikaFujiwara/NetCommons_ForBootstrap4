<?php
/**
 * NetCommonsControllerTestCase
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerBaseTestCase', 'NetCommons.TestSuite');
App::uses('NetCommonsCakeTestCase', 'NetCommons.TestSuite');

/**
 * NetCommonsControllerTestCase
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\NetCommons\TestSuite
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class NetCommonsControllerTestCase extends NetCommonsControllerBaseTestCase {

/**
 * Load TestPlugin
 *
 * @param CakeTestCase $test CakeTestCase
 * @param string $plugin Plugin name
 * @param string $testPlugin Test plugin name
 * @return void
 */
	public static function loadTestPlugin(CakeTestCase $test, $plugin, $testPlugin) {
		NetCommonsCakeTestCase::loadTestPlugin($test, $plugin, $testPlugin);
	}

/**
 * Lets you do functional tests of a controller action.
 *
 * ### Options:
 *
 * - `data` Will be used as the request data. If the `method` is GET,
 *   data will be used a GET params. If the `method` is POST, it will be used
 *   as POST data. By setting `$options['data']` to a string, you can simulate XML or JSON
 *   payloads to your controllers allowing you to test REST webservices.
 * - `method` POST or GET. Defaults to POST.
 * - `return` Specify the return type you want. Choose from:
 *     - `vars` Get the set view variables.
 *     - `view` Get the rendered view, without a layout.
 *     - `contents` Get the rendered view including the layout.
 *     - `result` Get the return value of the controller action. Useful
 *       for testing requestAction methods.
 * - `type` json or html, Defaults to html.
 *
 * @param string $url The url to test
 * @param array $options See options
 * @return mixed
 */
	protected function _testAction($url = '', $options = []) {
		$options = array_merge(['type' => 'html'], $options);
		if ($options['type'] === 'json') {
			$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
			$_SERVER['HTTP_ACCEPT'] = 'application/json';
		}
		$ret = parent::_testAction($url, $options);
		return $ret;
	}

/**
 * Assert input tag
 *
 * ### $return????????????
 *  - viewFile: view????????????????????????
 *  - json: JSON????????????????????????????????????
 *  - ????????????: $this->testAction???return??????????????????????????????
 *
 * @param array $url URL??????
 * @param array $paramsOptions ?????????????????????????????????????????????
 * @param string|null $exception Exception
 * @param string $return testAction?????????????????????
 * @return mixed
 */
	protected function _testNcAction($url = [], $paramsOptions = [],
										$exception = null, $return = 'view') {
		if ($exception && $return !== 'json') {
			$this->setExpectedException($exception);
		}

		//URL??????
		$params = array();
		if ($return === 'viewFile') {
			$params['return'] = 'view';
		} elseif ($return === 'json') {
			$params['return'] = 'view';
			$params['type'] = 'json';
			if ($exception === 'BadRequestException') {
				$status = 400;
			} elseif ($exception === 'ForbiddenException') {
				$status = 403;
			} else {
				$status = 200;
			}
		} else {
			$params['return'] = $return;
		}
		$params = Hash::merge($params, $paramsOptions);

		//???????????????
		$view = $this->testAction(NetCommonsUrl::actionUrl($url), $params);
		if ($return === 'viewFile') {
			$result = $this->controller->view;
		} elseif ($return === 'json') {
			$result = json_decode($this->contents, true);
			$this->assertArrayHasKey('code', $result);
			$this->assertEquals($status, $result['code']);
		} else {
			$result = $view;
		}

		return $result;
	}

/**
 * view???????????????????????????
 *
 * @param array $urlOptions URL???????????????
 * @param array $assert ?????????????????????
 * @param string|null $exception Exception
 * @param string $return testAction?????????????????????
 * @return mixed ???????????????
 */
	protected function _testGetAction($urlOptions, $assert, $exception = null, $return = 'view') {
		//???????????????
		if (is_array($urlOptions)) {
			$url = Hash::merge(array(
				'plugin' => $this->plugin,
				'controller' => $this->_controller,
			), $urlOptions);
		} else {
			$url = $urlOptions;
		}
		$result = $this->_testNcAction($url, array('method' => 'get'), $exception, $return);

		if (! $exception && $assert) {
			if ($assert['method'] === 'assertActionLink') {
				$assert['url'] = Hash::merge($url, $assert['url']);
			}

			$this->asserts(array($assert), $result);
		}

		return $result;
	}

/**
 * add??????????????????POST?????????
 *
 * @param array $method ??????????????????method(post put delete)
 * @param array $data POST?????????
 * @param array $urlOptions URL???????????????
 * @param string|null $exception Exception
 * @param string $return testAction?????????????????????
 * @return mixed ???????????????
 */
	protected function _testPostAction($method, $data, $urlOptions,
											$exception = null, $return = 'view') {
		//???????????????
		if (is_array($urlOptions)) {
			$url = Hash::merge(array(
				'plugin' => $this->plugin,
				'controller' => $this->_controller,
			), $urlOptions);
		} else {
			$url = $urlOptions;
		}
		$result = $this->_testNcAction($url, ['method' => $method, 'data' => $data], $exception, $return);

		return $result;
	}

/**
 * add??????????????????ValidateionError?????????
 *
 * @param array $method ??????????????????method(post put delete)
 * @param array $data POST?????????
 * @param array $urlOptions URL???????????????
 * @param string|null $validError ValidationError
 * @return mixed ???????????????
 */
	protected function _testActionOnValidationError($method, $data, $urlOptions, $validError = null) {
		$data = Hash::remove($data, $validError['field']);
		$data = Hash::insert($data, $validError['field'], $validError['value']);

		//???????????????
		$url = Hash::merge(array(
			'plugin' => $this->plugin,
			'controller' => $this->_controller,
		), $urlOptions);
		$result = $this->_testNcAction($url, array('method' => $method, 'data' => $data));

		//??????????????????????????????
		$asserts = array(
			array('method' => 'assertNotEmpty', 'value' => $this->controller->validationErrors),
			array('method' => 'assertTextContains', 'expected' => $validError['message']),
		);

		//????????????
		$this->asserts($asserts, $result);

		return $result;
	}

/**
 * ?????????False???Mock?????????
 *
 * @param string $mockModel Mock????????????
 * @param string $mockMethod Mock???????????????
 * @param int|string $count Mock?????????????????????
 * @return void
 */
	protected function _mockForReturnFalse($mockModel, $mockMethod, $count = 1) {
		$this->_mockForReturn($mockModel, $mockMethod, false, $count);
	}

/**
 * ?????????True???Mock?????????
 *
 * @param string $mockModel Mock????????????
 * @param string $mockMethod Mock???????????????
 * @param int|string $count Mock?????????????????????
 * @return void
 */
	protected function _mockForReturnTrue($mockModel, $mockMethod, $count = 1) {
		$this->_mockForReturn($mockModel, $mockMethod, true, $count);
	}

/**
 * ??????????????????Mock?????????
 *
 * @param string $mockModel Mock????????????
 * @param string $mockMethod Mock???????????????
 * @param bool $return ?????????
 * @param int|string $count Mock?????????????????????
 * @return void
 */
	protected function _mockForReturn($mockModel, $mockMethod, $return, $count = 1) {
		list($mockPlugin, $mockModel) = pluginSplit($mockModel);

		if (empty($this->controller->$mockModel) ||
				substr(get_class($this->controller->$mockModel), 0, strlen('Mock_')) !== 'Mock_') {
			$this->controller->$mockModel = $this->getMockForModel(
				$mockPlugin . '.' . $mockModel,
				array($mockMethod),
				array('plugin' => Inflector::underscore($mockPlugin))
			);
		}
		if ($count === 'any') {
			$funcCount = $this->any();
		} elseif ($count === 1) {
			$funcCount = $this->once();
		} else {
			$funcCount = $this->exactly($count);
		}
		$this->controller->$mockModel->expects($funcCount)
			->method($mockMethod)
			->will($this->returnValue($return));
	}

/**
 * Callback???Mock?????????
 *
 * @param string $mockModel Mock????????????
 * @param string $mockMethod Mock???????????????
 * @param mixed $callback ????????????????????????
 * @return void
 */
	protected function _mockForReturnCallback($mockModel, $mockMethod, $callback) {
		list($mockPlugin, $mockModel) = pluginSplit($mockModel);

		if (empty($this->controller->$mockModel) ||
				substr(get_class($this->controller->$mockModel), 0, strlen('Mock_')) !== 'Mock_') {
			$this->controller->$mockModel = $this->getMockForModel(
				$mockPlugin . '.' . $mockModel, array($mockMethod)
			);
		}
		$funcCount = $this->once();
		$this->controller->$mockModel->expects($funcCount)
			->method($mockMethod)
			->will($this->returnCallback($callback));
	}

/**
 * Asserts
 *
 * @param array $asserts ?????????Assert
 * @param string $result Result data
 * @return void
 */
	public function asserts($asserts, $result) {
		//????????????
		if (isset($asserts)) {
			foreach ($asserts as $assert) {
				$assertMethod = $assert['method'];

				if ($assertMethod === 'assertInput') {
					$this->$assertMethod($assert['type'], $assert['name'], $assert['value'], $result);
					continue;
				}

				if ($assertMethod === 'assertActionLink') {
					$this->$assertMethod($assert['action'], $assert['url'], $assert['linkExist'], $result);
					continue;
				}

				if (! isset($assert['value'])) {
					$assert['value'] = $result;
				}
				if (isset($assert['expected'])) {
					$this->$assertMethod($assert['expected'], $assert['value']);
				} else {
					$this->$assertMethod($assert['value']);
				}
			}
		}
	}

/**
 * Assert input tag
 *
 * @param string $tagType ???????????????(input or textearea or button)
 * @param string $name input?????????name??????
 * @param string $value input?????????value???
 * @param string $result Result data
 * @param string $message ???????????????
 * @return void
 */
	public function assertInput($tagType, $name, $value, $result, $message = null) {
		$result = str_replace("\n", '', $result);

		if ($name) {
			$patternName = '.*?name="' . preg_quote($name, '/') . '"';
		} else {
			$patternName = '';
		}

		if (! $value) {
			$patternValue = '';
		} elseif (in_array($value, ['checked', 'selected'], true)) {
			$patternValue = '.*?' . preg_quote($value, '/') . '="' . preg_quote($value, '/') . '"';
		} else {
			$patternValue = '.*?value="' . preg_quote($value, '/') . '"';
		}

		if ($tagType === 'textarea') {
			$this->assertRegExp(
				'/<textarea' . $patternName . '.*?>.*?' . preg_quote($value, '/') . '<\/textarea>/',
				$result, $message
			);
		} elseif ($tagType === 'option') {
			$this->assertRegExp(
				'/<option.*?value="' . preg_quote($name, '/') . '"' . $patternValue . '.*?>/',
				$result, $message
			);
		} elseif ($tagType === 'form') {
			$this->assertRegExp(
				'/<form.*?action=".*?' . preg_quote($value, '/') . '.*"' . $patternName . '.*?>/',
				$result, $message
			);
		} elseif (in_array($tagType, ['input', 'select', 'button'], true)) {
			$this->assertRegExp(
				'/<' . $tagType . $patternName . $patternValue . '.*?>/', $result, $message
			);
		}
	}

/**
 * Assert ????????????????????????
 *
 * @param string $action ???????????????
 * @param array $urlOptions URL???????????????
 * @param bool $linkExist ??????????????????
 * @param string $result Result data
 * @param string $message ???????????????
 * @return void
 */
	public function assertActionLink($action, $urlOptions, $linkExist, $result, $message = null) {
		$url = Hash::merge(array(
			'plugin' => $this->plugin,
			'controller' => $this->_controller,
		), $urlOptions);

		$url['action'] = $action;

		if (isset($url['frame_id']) && ! Current::read('Frame.id')) {
			unset($url['frame_id']);
		}
		if (isset($url['block_id']) && ! Current::read('Block.id')) {
			unset($url['block_id']);
		}

		if ($linkExist) {
			$method = 'assertRegExp';
		} else {
			$method = 'assertNotRegExp';
		}
		$expected = '/' . preg_quote(NetCommonsUrl::actionUrl($url), '/') . '/';

		//????????????
		$this->$method($expected, $result, $message);
	}

}
