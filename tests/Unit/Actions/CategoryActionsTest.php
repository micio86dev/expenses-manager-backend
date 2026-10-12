<?php

declare(strict_types=1);

use App\Actions\Categories\ArchiveCategory;
use App\Actions\Categories\CreateCategory;
use App\Actions\Categories\DeleteCategory;
use App\Actions\Categories\ListCategories;
use App\Actions\Categories\UnarchiveCategory;
use App\Actions\Categories\UpdateCategory;
use App\Data\CategoryData;
use App\Enums\CategoryIcon;
use App\Exceptions\CategoryInUseException;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\SystemCategoryReadOnlyException;
use Tests\Fakes\InMemoryCategoryRepository;

beforeEach(function (): void {
    $this->categories = new InMemoryCategoryRepository;
});

function categoryData(string $name = 'Pets'): CategoryData
{
    return new CategoryData($name, '#AA33CC', CategoryIcon::Heart);
}

it('lists categories by display name, hiding archived ones unless asked', function (): void {
    $this->categories->add('zebra');
    $this->categories->add('Apple');
    $this->categories->add('mango', archived: true);

    $list = new ListCategories($this->categories);

    expect(array_map(fn ($c) => $c->name, $list(false)))->toBe(['Apple', 'zebra'])
        ->and(array_map(fn ($c) => $c->name, $list(true)))->toBe(['Apple', 'mango', 'zebra']);
});

it('creates and updates a custom category', function (): void {
    $created = (new CreateCategory($this->categories))(categoryData());
    $updated = (new UpdateCategory($this->categories))($created->id, categoryData('Animals'));

    expect($updated->name)->toBe('Animals')->and($updated->id)->toBe($created->id);
});

it('never changes, deletes or archives a system category', function (): void {
    $system = $this->categories->add('groceries', system: true);

    expect(fn () => (new UpdateCategory($this->categories))($system->id, categoryData()))->toThrow(SystemCategoryReadOnlyException::class)
        ->and(fn () => (new DeleteCategory($this->categories))($system->id))->toThrow(SystemCategoryReadOnlyException::class)
        ->and(fn () => (new ArchiveCategory($this->categories))($system->id))->toThrow(SystemCategoryReadOnlyException::class)
        ->and(fn () => (new UnarchiveCategory($this->categories))($system->id))->toThrow(SystemCategoryReadOnlyException::class);
});

it('refuses to delete a category in use and deletes an unused one', function (): void {
    $used = $this->categories->add('Used');
    $unused = $this->categories->add('Unused');
    $this->categories->used = [$used->id];

    $delete = new DeleteCategory($this->categories);

    expect(fn () => $delete($used->id))->toThrow(CategoryInUseException::class);

    $delete($unused->id);

    expect($this->categories->categories)->toHaveKey($used->id)->not->toHaveKey($unused->id);
});

it('archives idempotently without touching the repository twice', function (): void {
    $category = $this->categories->add('Pets');
    $archive = new ArchiveCategory($this->categories);

    $first = $archive($category->id)->archived_at;
    $this->travel(1)->hour();
    $second = $archive($category->id)->archived_at;

    expect($first)->not->toBeNull()->and($second?->equalTo($first))->toBeTrue();

    expect((new UnarchiveCategory($this->categories))($category->id)->isArchived())->toBeFalse();
});

it('reports a missing category as not found', function (): void {
    expect(fn () => (new UpdateCategory($this->categories))('missing', categoryData()))->toThrow(ResourceNotFoundException::class);
});
