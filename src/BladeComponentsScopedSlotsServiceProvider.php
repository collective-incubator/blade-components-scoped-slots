<?php

namespace KonradKalemba\BladeComponentsScopedSlots;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeComponentsScopedSlotsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('scopedslot', function ($expression) {
            // Split the expression by `top-level` commas (not in parentheses)
            $directiveArguments = preg_split("/,(?![^\(\(]*[\)\)])/", $expression);
            $directiveArguments = array_map('trim', $directiveArguments);

            // Ensure that the directive's arguments array has 3 elements - otherwise fill with `null`
            $directiveArguments = array_pad($directiveArguments, 3, null);

            // Extract values from the directive's arguments array
            [$name, $functionArguments, $functionUses] = $directiveArguments;

            // Wrap function arguments in parentheses if they don't already have them
            if ($functionArguments && !preg_match('/^\(.*\)$/', $functionArguments))
                $functionArguments = "({$functionArguments})";

            // Connect the arguments to form a correct function declaration
            if ($functionArguments) $functionArguments = "function {$functionArguments}";
            
            $functionUses = array_filter(explode(',', trim($functionUses, '()')), 'strlen');
            
            // Add `$__env` to allow usage of other Blade directives inside the scoped slot
            $functionUses[] = '$__env';

            // Add `$errors` to allow usage of the validation errors inside the scoped slot
            $functionUses[] = '$errors';

            $functionUses = implode(',', $functionUses);

            return "<?php \$__env->slot({$name}, {$functionArguments} use ({$functionUses}) { ob_start(); ?>";
        });

        Blade::directive('endscopedslot', function () {
            return "<?php return new \Illuminate\Support\HtmlString(trim(ob_get_clean())); }); ?>";
        });
    }
}