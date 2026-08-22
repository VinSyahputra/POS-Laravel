<?php

use App\Models\PrinterSetting;

it('shows default printer settings', function () {
    $this->getJson('/printer-settings')
        ->assertSuccessful()
        ->assertJsonPath('data.paper_width', '80mm');
});

it('updates printer settings', function () {
    $this->putJson('/printer-settings', [
        'printer_name' => 'Xprinter XP-58',
        'paper_width' => '58mm',
    ])->assertSuccessful();

    $setting = PrinterSetting::query()->first();

    expect($setting->printer_name)->toBe('Xprinter XP-58')
        ->and($setting->paper_width)->toBe('58mm');
});

it('rejects an invalid paper width', function () {
    $this->putJson('/printer-settings', ['paper_width' => '120mm'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('paper_width');
});
