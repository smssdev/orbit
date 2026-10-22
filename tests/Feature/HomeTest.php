<?php

test('home page renders', function (): void {
    $response = $this->get(route('home'));

    $response->assertOk();
});
