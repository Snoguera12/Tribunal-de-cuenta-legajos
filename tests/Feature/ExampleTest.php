<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    // la raiz manda al panel
    public function test_la_raiz_redirige_al_panel(): void
    {
        $this->get('/')->assertRedirect('legajos');
    }


    // un link de documento sin firma no sirve
    public function test_un_documento_sin_login_no_se_abre(): void
    {
        $this->get('/documentos/algo.pdf')->assertStatus(401);
    }
}
