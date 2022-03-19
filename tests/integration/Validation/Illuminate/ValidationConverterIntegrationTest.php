<?php


namespace Ems\Validation\Illuminate;

use Ems\Contracts\Validation\ValidationConverter as ValidationConverterContract;
use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Contracts\Validation\Validation;
use Ems\Contracts\Expression\ConstraintParsingMethods;
use Illuminate\Support\MessageBag;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Ems\Skeleton\Application;
use Ems\Core\Helper;
use Ems\Validation\ValidationException;

/**
 * @group validation
 **/
class ValidationConverterIntegrationTest extends \Ems\LaravelIntegrationTest
{

    use ConstraintParsingMethods;

    /**
     * @var array
     **/
    protected $messages = [];

    public function test_implements_interface()
    {
        $this->assertInstanceOf(ValidationConverter::class, $this->newConverter());
    }

    public function test_convert_required()
    {

        $rules = [
            'login'    => 'required',
            'password' => 'required'
        ];

        $validator = $this->newValidator($rules);

        try {
            $validator->validate([]);
            $this->fail('Validate should fail');
        } catch (Validation $validation) {

            $messageBag = $this->convert($validation);

            $awaited = [];

            foreach ($rules as $key=>$rule) {
                $awaited[$key] = (array)str_replace(':attribute', $key, $this->message('required'));
            }

            $this->assertEquals($awaited, $messageBag->messages());

        }
    }

    public function test_convert_to_empty_bag_if_validation_is_empty()
    {

        $validation = new ValidationException;

        $this->assertCount(0, $this->convert($validation));

    }

    /**
     * @expectedException Ems\Core\Exceptions\UnsupportedParameterException
     **/
    public function test_convert_throws_exception_if_format_unsupported()
    {

        $validation = new ValidationException;

        $this->convert($validation, [], [], 'stdClass');

    }

    public function test_convert_non_replaced_rules_with_string_size()
    {

        $rules = [
            'email'    => 'min:3|email',
            'password' => 'max:5'
        ];

        $validator = $this->newValidator($rules);

        try {
            $validator->validate(['email' => 'a', 'password' => 'basdkjhwedasuihlkjh']);
            $this->fail('Validate should fail');
        } catch (Validation $validation) {

            $messageBag = $this->convert($validation);

            $awaited = [];

            $replace = [
                ':min' => '3',
                ':max' => '5'
            ];

            $parsedRules = $this->parseConstraints($rules);

             foreach ($parsedRules as $key=>$rules) {

                $awaited[$key] = [];

                foreach ($rules as $name=>$parameters) {

                    $lKey = $name == 'email' ? $name : "$name.string";

                    $message = str_replace(':attribute', $key, $this->message($lKey));

                    $message = str_replace(array_keys($replace), array_values($replace), $message);

                    $awaited[$key][] = $message;

                }

             }

            $this->assertEquals($awaited, $messageBag->messages());

        }
    }

    public function test_convert_non_replaced_rules_with_numeric_size()
    {

        $rules = [
            'email'    => 'numeric|min:3|email',
            'password' => 'numeric|max:5'
        ];

        $validator = $this->newValidator($rules);

        try {
            $validator->validate(['email' => 'a', 'password' => 'basdkjhwedasuihlkjh']);
            $this->fail('Validate should fail');
        } catch (Validation $validation) {

            $messageBag = $this->convert($validation);

            $awaited = [];

            $replace = [
                ':min' => '3',
                ':max' => '5'
            ];

            $parsedRules = $this->parseConstraints($rules);

             foreach ($parsedRules as $key=>$rules) {

                $awaited[$key] = [];

                foreach ($rules as $name=>$parameters) {

                    $lKey = in_array($name, ['email', 'numeric']) ? $name : "$name.numeric";

                    $message = str_replace(':attribute', $key, $this->message($lKey));

                    $message = str_replace(array_keys($replace), array_values($replace), $message);

                    $awaited[$key][] = $message;

                }

             }

            $this->assertEquals($awaited, $messageBag->messages());

        }
    }

    public function test_convert_non_replaced_rules_with_array_size()
    {

        $rules = [
            'email'    => 'array|min:3',
            'password' => 'array|max:5'
        ];

        $validator = $this->newValidator($rules);

        try {
            $validator->validate(['email' => 'a', 'password' => 'basdkjhwedasuihlkjh']);
            $this->fail('Validate should fail');
        } catch (Validation $validation) {

            $messageBag = $this->convert($validation);

            $awaited = [];

            $replace = [
                ':min' => '3',
                ':max' => '5'
            ];

            $parsedRules = $this->parseConstraints($rules);

             foreach ($parsedRules as $key=>$rules) {

                $awaited[$key] = [];

                foreach ($rules as $name=>$parameters) {

                    $lKey = in_array($name, ['array', 'numeric']) ? $name : "$name.array";

                    $message = str_replace(':attribute', $key, $this->message($lKey));

                    $message = str_replace(array_keys($replace), array_values($replace), $message);

                    $awaited[$key][] = $message;

                }

             }

            $this->assertEquals($awaited, $messageBag->messages());

        }
    }

    public function test_convert_non_replaced_rules_with_file_size()
    {

        $rules = [
            'email'    => 'file|min:3',
            'password' => 'file|max:5'
        ];

        $validator = $this->newValidator($rules);

        try {
            $validator->validate(['email' => 'a', 'password' => 'basdkjhwedasuihlkjh']);
            $this->fail('Validate should fail');
        } catch (Validation $validation) {

            $messageBag = $this->convert($validation);

            $awaited = [];

            $replace = [
                ':min' => '3',
                ':max' => '5'
            ];

            $parsedRules = $this->parseConstraints($rules);

             foreach ($parsedRules as $key=>$rules) {

                $awaited[$key] = [];

                foreach ($rules as $name=>$parameters) {

                    $lKey = in_array($name, ['array', 'numeric']) ? $name : "$name.file";

                    if ($name == 'file') {
                        $awaited[$key][] = 'validation.file';
                        continue;
                    }

                    $message = str_replace(':attribute', $key, $this->message($lKey));

                    $message = str_replace(array_keys($replace), array_values($replace), $message);

                    $awaited[$key][] = $message;

                }

             }

            $this->assertEquals($awaited, $messageBag->messages());

        }
    }

    public function test_convert_replaced_rules()
    {

        $rules = [
            'email'    => 'between:3,6',
            'password' => 'same:email'
        ];

        $validator = $this->newValidator($rules);

        try {
            $validator->validate(['email' => 'a', 'password' => 'basdkjhwedasuihlkjh']);
            $this->fail('Validate should fail');
        } catch (Validation $validation) {

            $messageBag = $this->convert($validation);

            $awaited = [];

            $replace = [
                ':min' => '3',
                ':max' => '6',
                ':other' => 'email'
            ];

            $parsedRules = $this->parseConstraints($rules);

             foreach ($parsedRules as $key=>$rules) {

                $awaited[$key] = [];

                foreach ($rules as $name=>$parameters) {

                    $lKey = in_array($name, ['same']) ? $name : "$name.string";

                    $message = str_replace(':attribute', $key, $this->message($lKey));

                    $message = str_replace(array_keys($replace), array_values($replace), $message);

                    $awaited[$key][] = $message;

                }

             }

            $this->assertEquals($awaited, $messageBag->messages());

        }
    }

    public function test_convert_required_with_passed_custom_message_for_key()
    {

        $rules = [
            'login'    => 'required',
            'password' => 'required'
        ];

        $customMessages = [
            'login.required' => 'broken!'
        ];

        $validator = $this->newValidator($rules);

        try {
            $validator->validate([]);
            $this->fail('Validate should fail');
        } catch (Validation $validation) {

            $messageBag = $this->convert($validation, [], $customMessages);

            $awaited = [];

            foreach ($rules as $key=>$rule) {
                if ("$key.$rule" == 'login.required') {
                    $awaited[$key] = [$customMessages['login.required']];
                    continue;
                }
                $awaited[$key] = (array)str_replace(':attribute', $key, $this->message('required'));
            }

            $this->assertEquals($awaited, $messageBag->messages());

        }
    }

    public function test_convert_required_with_passed_custom_message_for_rule()
    {

        $rules = [
            'login'    => 'required',
            'password' => 'required'
        ];

        $customMessages = [
            'required' => 'broken!'
        ];

        $validator = $this->newValidator($rules);

        try {
            $validator->validate([]);
            $this->fail('Validate should fail');
        } catch (Validation $validation) {

            $messageBag = $this->convert($validation, [], $customMessages);


            $awaited = [];

            foreach ($rules as $key=>$rule) {
                $awaited[$key] = [$customMessages[$rule]];
            }

            $this->assertEquals($awaited, $messageBag->messages());

        }
    }

    public function test_convert_required_with_passed_custom_message_in_translation()
    {

        $rules = [
            'test'           => 'integer'
        ];

        $validator = $this->newValidator($rules);

        try {
            $validator->validate(['test' => 'foo']);
            $this->fail('Validate should fail');
        } catch (Validation $validation) {

            $messageBag = $this->convert($validation, []);


            $awaited = [];

            foreach ($rules as $key=>$rule) {
                $awaited[$key] = ['custom-message'];
            }

            $this->assertEquals($awaited, $messageBag->messages());

        }
    }

    protected function convert(Validation $validation, array $keyTitles = [], array $customMessages = [], $format=null)
    {
        $format = $format ?: MessageBag::class;
        return $this->newConverter()->convert($validation, $format, $keyTitles, $customMessages);
    }

    protected function newConverter()
    {
        return $this->app(ValidationConverterContract::class);
    }

    protected function newValidator(array $rules)
    {
        return $this->app(ValidatorFactoryContract::class)->make($rules);
    }

    protected function bootApplication(Application $app)
    {
        parent::bootApplication($app);

        $app = $app->getContainer()->laravel();

        $app->singleton('translation.loader', function ($app) {
            return (new ArrayLoader())->addMessages('en', 'validation', $this->messages());
        });

        // Replace Translator to have some messages
        $app->singleton('translator', function ($app) {
            return new Translator($app['translation.loader'], 'en');
        });

    }

    protected function messages()
    {
        if (!$this->messages) {
            $this->messages = include(static::dataFile('validation/illuminate/validation.php'));
        }
        return $this->messages;
    }

    protected function message($key)
    {
        return Helper::value($this->messages(), $key);
    }

}
