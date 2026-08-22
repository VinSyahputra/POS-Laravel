<?php

test('dashboard renders without login', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('POS FOOD COURT')
        ->assertSee('Kasir')
        ->assertSee('Menu');
});
