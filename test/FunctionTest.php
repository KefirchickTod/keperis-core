<?php


use PHPUnit\Framework\TestCase;

class FunctionTest extends TestCase
{

    public function test_current_user_id()
    {

        $this->assertSame(0, current_user_id());

        $_SESSION['BCUserId'] = 1264;

        $this->assertSame(1264, current_user_id());

    }

    public function test_router()
    {
        $this->assertInstanceOf(\src\Router\Router::class, router());
    }

    public function test_slug()
    {
        $title = 'Тест тест';

        $slug = slug($title);

        $this->assertSame('test-test', $slug);
    }

    public function test_html()
    {
        $html = html();

        $this->assertInstanceOf(\src\Core\easyCreateHTML::class, $html);

        $text = $html->a()->end('a')->render(true);

        $matcher = [
            'tag' => 'a',
        ];

        $this->assertSame("<a></a>", $text);
    }

    public function test_valid()
    {
        $validation = [
            'first'  => 'yes',
            'second' => 2,
        ];


        $this->assertSame('yes', valid($validation, 'first', ''));


        $this->assertSame(2, valid($validation, 'second'));

        $this->assertNotNull(valid($validation, 1, false));

        $this->assertNull(valid($validation, 1));
    }


    public function test_env()
    {
//        $debug = true;

        $evnDebug = env('APP_DEBUG', false);

        $this->assertTrue($evnDebug);
    }

    public function test_structure(){
        $structure = structure();

        $this->assertInstanceOf(\src\Structure\Structure::class, $structure);

    }
}
