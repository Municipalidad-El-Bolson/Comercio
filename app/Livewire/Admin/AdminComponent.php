<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class AdminComponent extends Component
{
    use WithPagination, WithoutUrlPagination;

	protected $paginationTheme = 'bootstrap';
}
