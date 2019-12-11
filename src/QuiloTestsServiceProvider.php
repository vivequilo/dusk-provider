<?php

namespace Quilo\Dusk;

use Exception;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\ServiceProvider;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Assert as PHPUnit;
use Tests\Browser\Constants\NavigationConstants;
use Tests\Browser\Constants\UserConstantsDusk;
use Tests\Browser\Pages\DirectoryGeneral;
use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\Logout;
use Tests\Browser\Pages\SelectBusiness;

class QuiloTestsServiceProvider extends ServiceProvider {
	/**
	 * Bootstrap any package services.
	 *
	 * @return void
	 */
	public function boot() {
		Browser::macro("logout", function () {
			$this->visit(new Logout)
				->on(new Login);
		});
		Browser::macro('scrollToElement', function ($element) {
			$type = (strpos($element, '#') !== false) ? 'id' : 'name';
			if ($type == 'id') {
				$element = str_replace('#', '', $element);
				$this->script("document.getElementById('$element').scrollIntoView();");
			} else {
				$this->script("document.getElementsByName('$element')[0].scrollIntoView();");
			}

			return $this;
		});
		Browser::macro('myLogin', function ($user = null, $business = null, $newBusiness = null) {
			$newBusiness = $newBusiness ?? true;
			$userArray   = UserConstantsDusk::returnUserArray($user);
			$this
				->visit('/logout')
				->loginAs($this->user->id)
				->visit(new SelectBusiness)
				->chooseBusiness($business, $newBusiness)
				->on(new DirectoryGeneral)
				->waitForThisLocation()
				->assert();

			return $this;
		});
		Browser::macro('clickJs', function ($selector = null, $filterValue = null, $script = null) {
			$type     = (strpos($selector, '#') !== false) ? 'id' : 'name';
			$type     = (strpos($selector, 'class-') !== false) ? 'class' : $type;
			$selector = ($type == 'id') ? str_replace("#", "", $selector) : str_replace($type . "-", "", $selector);

			if ($type !== 'class' || $filterValue === null) {
				$this->emulateClick($selector, $type);
			} else if ($type === 'class') {
				$script = $script ?? 'normal';
				$this->script(NavigationConstants::myClickScript($selector, $filterValue, $script));
			}

			return $this;
		});

		Browser::macro('clickJsUntilColl', function ($click_selector = null, $missing_elements_value = null, $filterValue = null) {
			$missing_elements_value = gettype($missing_elements_value) === 'array' ? collect($missing_elements_value) : $missing_elements_value;
			if ($missing_elements_value->isNotEmpty()) {
				foreach ($missing_elements_value as $key => $missing_element) {
					$missingSelector = $missing_element['selector'];
					$missingValue    = $missing_element['filter_value'];
					while ($this->script(NavigationConstants::missingValueScript($missingSelector, $missingValue))[0]) {
						$bool = $this->script(NavigationConstants::missingValueScript($missingSelector, $missingValue))[0];
						if ($bool) {
							$this->pause(200);
							$this->clickJs($click_selector, $filterValue);
						}
					}
				}
			}

			return $this;
		});

		Browser::macro('assertNameHrefUrl', function ($name = null, $href = null, $link = null) {
			$message = "Did not see expected link [{$link}] with href [{$href}].";
			if ($this->resolver->prefix) {
				$message .= " within [{$this->resolver->prefix}].";
			}
			$anchorName = $this->driver->findElement(WebDriverBy::name($name));
			$hrefGet    = $anchorName->getAttribute('href');
			$hasText    = $anchorName && (strpos($anchorName->getText(), $link) !== false);
			$sameHref   = $hrefGet === env('APP_URL') . '/' . $href;
			PHPUnit::assertTrue($anchorName && $hasText && $sameHref, $message);

			return $this;
		});

		Browser::macro('assertNameHref', function ($name = null, $href = null) {
			$message = "Did not see expected name [{$name}] with href [{$href}].";
			if ($this->resolver->prefix) {
				$message .= " within [{$this->resolver->prefix}].";
			}
			$anchorName = $this->driver->findElement(WebDriverBy::name($name));
			$hrefGet    = $anchorName->getAttribute('href');
			$sameHref   = $hrefGet === $href;
			PHPUnit::assertTrue($anchorName && $sameHref, $message);

			return $this;
		});

		Browser::macro('assertAttributeIs', function ($name = null, $attributeValue = null, $attribute = null) {
			$message = "Did not see expected name [{$name}] with [{$attribute}] [{$attributeValue}].";
			if ($this->resolver->prefix) {
				$message .= " within [{$this->resolver->prefix}].";
			}
			$anchorName        = $this->driver->findElement(WebDriverBy::name($name));
			$attributeObtained = $anchorName->getAttribute($attribute);
			$attributeObtained = ($attribute === 'disabled' && $attributeObtained === null) ? 'false' : $attributeObtained;
			$matches           = $attributeObtained === $attributeValue;
			PHPUnit::assertTrue($anchorName && $matches, $message);

			return $this;
		});

		Browser::macro('collectionAssertSee', function ($values = null) {
			$collection = gettype($values) === 'array' ? collect($values) : $values;
			$collection->map(function ($value) {
				$this->assertSee($value);
			});

			return $this;
		});

		Browser::macro('collectionAssertSeeIn', function ($values = null) {
			$collection = gettype($values) === 'array' ? collect($values) : $values;
			$collection->map(function ($value, $key) {
				if ($key !== '' || is_int($key)) {
					$this->assertSeeIn('#' . $key, $value);
				} else {
					$this->assertSee($value);

				}
			});

			return $this;
		});
		Browser::macro('assertObjectValuesVue', function ($objectName = null, $objectPropertiesAndValues = null, $component = null) {
			$collection = gettype($objectPropertiesAndValues) === 'array' ? collect($objectPropertiesAndValues) : $objectPropertiesAndValues;
			$collection->map(function ($object) use ($objectName, $component) {
				$model = array_key_exists('vue-name', $object) ? $objectName . '.' . $object["vue-name"] : $objectName . '.' . $object["name"];
				$this->assertVue($model, $object['value'], $component);
			});

			return $this;
		});
		Browser::macro('assertObjectFormEmptyVue', function ($objectName = null, $objectProperties = null, $component = null) {
			$collection = gettype($objectProperties) === 'array' ? collect($objectProperties) : $objectProperties;
			$collection->map(function ($object) use ($objectName, $component) {
				$model = array_key_exists('vue-name', $object) ? $objectName . '.' . $object["vue-name"] : $objectName . '.' . $object["name"];
				$this->assertVue($model, '', $component);
			});

			return $this;
		});
		Browser::macro('typeInOrder', function ($inputs = null) {
			$collection = gettype($inputs) === 'array' ? collect($inputs) : $inputs;
			$collection->map(function ($input, $key) {
				$input["type"] = array_key_exists("type", $input) ? $input["type"] : 'type';
				switch ($input["type"]) {
					case 'select':
						// When the input is normal type

						$this->select($input["selector"], $input["value"]);
						break;
					case 'date':
						//When selector is a date
						$this->insertDate($input);
						break;
					case 'checkbox':
						//When selector is a checkbox
						$this->check($input["selector"]);
						break;
					case 'v-select':
						$values = collect($input["value"]["values"]);
						if ($input["v-select-type"] === "models") {
							if ($values->isEmpty()) {
								$this->checkVSelectIsEmpty('#' . $input["id"], $input);
							} else {
								$this->checkVSelectHasOptions('#' . $input["id"], $values);
							}
						}
						$name = $input["value"]["name"];
						$values->map(function ($value) use ($input, $name) {
							$this->setVselectValue($input["id"], $name, $value);
						});
						break;

					default:
						// When the input is normal type

						$this->type($input["selector"], $input["value"]);
						break;
				}
				// selector-check is the id of the span that is going to change if its valid
				$input["selector-check"] = array_key_exists("selector-check", $input) ? $input["selector-check"] : 'none';
				$input["class-name"]     = array_key_exists("class-name", $input) ? $input["class-name"] : 'none';
				if ($input["selector-check"] !== "none") {
					$this->hasClass($input["selector-check"], $input["class-name"]);
				}
			});

			return $this;
		});

		Browser::macro('clearInOrder', function ($inputs = null) {
			$collection = gettype($inputs) === 'array' ? collect($inputs) : $inputs;
			$collection->map(function ($input) {
				$this->clear($input['selector']);
			});

			return $this;
		});
		Browser::macro('insertDate', function ($input) {
			// Especify the type of date you are going to enter
			if ($input['dateType'] == 'flatpicker') {
				// If it has more than one flatpick we use the parent id to determine wich flatpickr we are going to focus
				$input["parentId"] = array_key_exists("parentId", $input) ? $input["parentId"] : null;
				$this->focusJs('date-flatpickr', $input["parentId"])
					->clickJsUntilColl($input['month-direction'], $input['year-month'])
					->clickJs('class-flatpickr-day', (string) $input["day-required"], 'flatpickr-day');
			} else if ($input['dateType'] == 'datepicker') {
				$this->click('#' . $input['id'])
					->clickJsUntil($input['direction'], 'year', $input['year'])
					->clickJs('class-year', $input['year'])->pause(500)
					->clickJs('class-month', $input['month'])->pause(500)
					->clickJs('class-day', $input['day'])->pause(500);
			}
		});

		Browser::macro('focusJs', function ($selector = null, $secondSelector = null) {
			if (strpos($selector, 'date') === false) {
				$type     = (strpos($selector, '#') !== false) ? 'id' : 'name';
				$type     = (strpos($selector, 'class-') !== false) ? 'class' : $type;
				$selector = ($type == 'id') ? str_replace("#", "", $selector) : str_replace($type . "-", "", $selector);

				$this->script(NavigationConstants::focusElementScript($type, $selector, $secondSelector));
			} else if (strpos($selector, 'flatpickr') !== false) {
				$secondSelector = str_replace("#", "", $secondSelector);
				$this->script(NavigationConstants::focusFlatPickrScript($secondSelector));
			}
			// uk-input uk-border-rounded flatpickr-input form-control input

			return $this;
		});

		Browser::macro('emulateClick', function ($selector = null, $type = null) {
			$this->script(NavigationConstants::emulateClickScript($type, $selector));

			return $this;
		});
		Browser::macro('hasClass', function ($selector, $className) {
			$type     = (strpos($selector, '#') !== false) ? 'id' : 'name';
			$type     = (strpos($selector, 'class-') !== false) ? 'class' : $type;
			$selector = ($type == 'id') ? str_replace("#", "", $selector) : str_replace($type . "-", "", $selector);
			$bool     = $this->script(NavigationConstants::elementHasClass($type, $selector, $className))[0];
			PHPUnit::assertTrue($bool);

			return $this;
		});
		Browser::macro('collHasClass', function ($values) {
			$values = (gettype($values) == 'array') ? collect($values) : $values;
			$values->map(function ($value) {
				$this->hasClass($value['selector-check'], $value['class-name']);
			});

			return $this;
		});
	}
	/**
	 * Register any package services.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function register() {

	}
}