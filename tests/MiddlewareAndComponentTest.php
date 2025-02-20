<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Torchlight\Middleware\RenderTorchlight;

class MiddlewareAndComponentTest extends BaseTest
{
    public function getEnvironmentSetUp($app)
    {
        config()->set('torchlight.blade_components', true);
        config()->set('torchlight.token', 'token');
    }

    protected function getView($view)
    {
        // This helps when testing multiple Laravel versions locally.
        $this->artisan('view:clear');

        Route::get('/torchlight', function () use ($view) {
            return View::file(__DIR__ . '/Support/' . $view);
        })->middleware(RenderTorchlight::class);

        return $this->call('GET', 'torchlight');
    }

    /** @test */
    public function it_sends_a_simple_request_with_no_response()
    {
        $this->fakeNullResponse('component');

        $response = $this->getView('simple-php-hello-world.blade.php');

        $this->assertEquals(
            '<pre><code class="torchlight" style=""><div class=\'line\'>echo &quot;hello world&quot;;</div></code></pre>',
            $response->content()
        );

        Http::assertSent(function ($request) {
            return $request['blocks'][0] === [
                'id' => 'component',
                'hash' => '66192c35bf8a710bee532ac328c76977',
                'language' => 'php',
                'theme' => 'material-theme-palenight',
                'code' => 'echo "hello world";',
            ];
        });
    }

    /** @test */
    public function it_sends_a_simple_request_with_highlighted_response()
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight',
            'styles' => 'background-color: #292D3E;',
            'highlighted' => 'this is the highlighted response from the server',
        ]);

        $response = $this->getView('simple-php-hello-world.blade.php');

        $this->assertEquals(
            '<pre><code class="torchlight" style="background-color: #292D3E;">this is the highlighted response from the server</code></pre>',
            $response->content()
        );
    }

    /** @test */
    public function classes_get_merged()
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight',
            'styles' => 'background-color: #292D3E;',
            'highlighted' => 'this is the highlighted response from the server',
        ]);

        $response = $this->getView('simple-php-hello-world-with-classes.blade.php');

        $this->assertEquals(
            '<code class="torchlight mt-4" style="background-color: #292D3E;">this is the highlighted response from the server</code>',
            $response->content()
        );
    }

    /** @test */
    public function attributes_are_preserved()
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight',
            'styles' => 'background-color: #292D3E;',
            'highlighted' => 'this is the highlighted response from the server',
        ]);

        $response = $this->getView('simple-php-hello-world-with-attributes.blade.php');

        $this->assertEquals(
            '<code class="torchlight" style="background-color: #292D3E;" x-data="{}">this is the highlighted response from the server</code>',
            $response->content()
        );
    }

    /** @test */
    public function inline_keeps_its_spaces()
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight',
            'styles' => 'background-color: #292D3E;',
            'highlighted' => 'this is the highlighted response from the server',
        ]);

        $response = $this->getView('an-inline-component.blade.php');

        $this->assertEquals(
            'this is <code class="torchlight" style="background-color: #292D3E;">this is the highlighted response from the server</code> inline',
            $response->content()
        );
    }

    /** @test */
    public function language_can_be_set_via_component()
    {
        $this->fakeNullResponse('component');

        $this->getView('simple-js-hello-world.blade.php');

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['language'] === 'javascript';
        });
    }

    /** @test */
    public function theme_can_be_set_via_component()
    {
        $this->fakeNullResponse('component');

        $this->getView('simple-php-hello-world-new-theme.blade.php');

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['theme'] === 'a new theme';
        });
    }

    /** @test */
    public function code_contents_can_be_a_file()
    {
        $this->fakeNullResponse('component');

        $this->getView('contents-via-file.blade.php');

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['code'] === rtrim(file_get_contents(config_path('app.php'), '\n'));
        });
    }

    /** @test */
    public function code_contents_can_be_a_file_2()
    {
        $this->fakeNullResponse('component');

        $this->getView('contents-via-file-2.blade.php');

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['code'] === rtrim(file_get_contents(config_path('app.php'), '\n'));
        });
    }

    /** @test */
    public function two_components_work()
    {
        $this->fakeSuccessfulResponse('component1', [
            'id' => 'component1',
            'classes' => 'torchlight1',
            'styles' => 'background-color: #111111;',
            'highlighted' => 'response 1',
        ]);

        $this->fakeSuccessfulResponse('component2', [
            'id' => 'component2',
            'classes' => 'torchlight2',
            'styles' => 'background-color: #222222;',
            'highlighted' => 'response 2',
        ]);

        $response = $this->getView('two-simple-php-hello-world.blade.php');

        $expected = <<<EOT
<pre><code class="torchlight1" style="background-color: #111111;">response 1</code></pre>

<pre><code class="torchlight2" style="background-color: #222222;">response 2</code></pre>
EOT;

        $this->assertEquals($expected, $response->content());
    }
}
