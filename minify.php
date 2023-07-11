<?php

/**
 * Минификатор JS и CSS через API сайта https://www.toptal.com/
 */
class Minify {
	/**
	 * @var string - Ссылка для запроса к API для минификации JS
	 */
	private $api_js = 'https://www.toptal.com/developers/javascript-minifier/api/raw';
	/**
	 * @var string - Ссылка для запроса к API для минификации CSS
	 */
	private $api_css = 'https://www.toptal.com/developers/cssminifier/api/raw';
	/**
	 * @var mixed|string - Кодировка файла
	 */
	private $encoding = 'UTF-8';
	/**
	 * @var bool - Применяется для проверки на измерение кодировки
	 */
	private $is_utf8 = true;
	/**
	 * @var bool|mixed - Применяется для проверки на обновление файлов
	 */
	private $update_all  = true;
	/**
	 * @var array - Массив файлов для минификации
	 */
	private $minify_path = array();

	/**
	 * Инициализируем класс
	 *
	 * @param $update_all - Обновлять все файлы?
	 *                      true - Обновит всё файлы
	 *                      false - Обновит только те файлы, которые были изменены
	 * @param $encoding - Исходная кодировка файлов. По умолчанию - UTF-8. Принимает строку с нужной кодировкой файлов
	 */
	public function __construct( $update_all = true, $encoding = null ) {
		if ( ! is_null( $encoding ) ) {
			$this->is_utf8  = false;
			$this->encoding = $encoding;
		}

		$this->update_all = $update_all;
	}

	/**
	 * Добавляем стиль/скрипт в массив для минификации
	 *
	 * @param $path - Путь до скрипта
	 *
	 * @return void
	 */
	public function add( $path ) {
		$this->minify_path[] = $path;
	}

	/**
	 * Минифицируем файл
	 *
	 * @return void
	 */
	public function minify() {
		// Проверяем что наш массив имеет пути
		if ( ! empty( $this->minify_path ) ) {
			// Перебираем наш массив и обрабатываем каждый путь
			foreach ( $this->minify_path as $file_path ) {
				// Проверяем что файл есть
				if ( file_exists( $file_path ) ) {

					// Получаем имя нового файла
					$name = $this->get_minify_name( $file_path );
					$api_url = $this->get_api_url( $file_path );

					// Делаем проверку на актуальность
					if ( $this->is_actual_version( $file_path, $name ) && $api_url ) {
						// Открываем "новый" файл или создаем его
						$handler = fopen( $name, 'w' ) or die( "Ошибка создания файла <b>" . $name . '</b><br />' );
						// Записываем ответ в файл
						fwrite( $handler, $this->get_minify( $api_url, file_get_contents( $file_path ) ) );
						// Закрываем файл
						fclose( $handler );
						echo "Файл <b>" . $name . '</b> создан!<br />';
					} else {
						echo "Файл <b>" . $name . '</b> пропущен. В файлах <b>' . $file_path . '</b> нет изменений.<br />';
					}
				} else {
					echo "Файл <b>" . $file_path . '</b> не найден. Проверьте правильность пути.<br />';
				}

			}
		}
	}

	/**
	 * Формируем имя минифицированому файлу
	 *
	 * @param $path - Путь до минифицируемого файла
	 *
	 * @return mixed|string
	 */
	private function get_minify_name( $path ) {
		// Получаем информацию о пути файла
		$file_info = pathinfo( $path );

		// Если есть расширение, то формируем "новое" имя
		if ( key_exists( 'extension', $file_info ) ) {
			if ( $file_info['dirname'] === '.' ) {
				$file_info['dirname'] = '';
			} else {
				$file_info['dirname'] .= '/';
			}

			return $file_info['dirname'] . $file_info['filename'] . '.min.' . $file_info['extension'];
		}

		return $path;
	}

	/**
	 * Получаем правильный URL для запросов к API по расширению файла
	 *
	 * @param $path - Путь до минифицируемого файла
	 *
	 * @return false|string
	 */
	private function get_api_url( $path ) {
		$file_info = pathinfo( $path );

		if ( key_exists( 'extension', $file_info ) ) {
			switch ( $file_info['extension'] ) {
				case 'css':
					return $this->api_css;
				case 'js':
					return $this->api_js;
				default:
					return false;
			}
		}

		return false;
	}

	/**
	 * Делаем запрос к API и возвращаем ответ
	 * TODO: Добавить проверку на ошибочные ответы API
	 *
	 * @param $url - Ссылка для запроса к API
	 * @param $content - Передаваемый для минификации код
	 *
	 * @return bool|string
	 */
	private function get_minify( $url, $content ) {
		$ch = curl_init();

		if ( ! $this->is_utf8 ) {
			$content = iconv( $this->encoding, 'UTF-8', $content );
		}

		curl_setopt_array( $ch, array(
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER     => array( "Content-Type: application/x-www-form-urlencoded" ),
			CURLOPT_POSTFIELDS     => http_build_query( array( "input" => $content ) )
		) );

		$minified = curl_exec( $ch );

		curl_close( $ch );

		if ( $this->is_utf8 ) {
			$minified = iconv( 'UTF-8', $this->encoding, $minified );
		}

		return $minified;
	}

	/**
	 * Проверка на актуальность минифицированной версии
	 *
	 * @param $file_path - Путь до минифицируемого файла
	 * @param $min_file_path - Путь до минифицированого файла
	 *
	 * @return bool
	 */
	private function is_actual_version( $file_path, $min_file_path ) {
		$actual = false;

		if ( $this->update_all || filemtime( $min_file_path ) < filemtime( $file_path ) ) {
			$actual = true;
		}

		return $actual;
	}

	/**
	 * Вспомогательная функция для дампа
	 *
	 * @param $code
	 *
	 * @return void
	 */
	private function vd( $code ) {
		var_dump( $code );
	}
}
