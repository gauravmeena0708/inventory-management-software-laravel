<?php

namespace Database\Factories;

use App\Models\Consumable;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsumableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Consumable::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $items = ['odonil',
        'envelope',
        'handwash',
        'harpic',
        'lizol',
        'colin',
        'dettol',
        'phenyl',
        'pocha',
        'notepad',
        'A4 rim',
        'All out Machine',
        'All out Refill',
        'Tissue Paper box',
        'Pilot Pen',
        'UPS',
        'Scale',
        'Register',
        'Ball pen',
        'Ball pen refill',
        'Mouse',
        'Mouse Pad',
        'Pen Stand',
        'Glue Stick',
        'Hit Spray',
        'Cell AA',
        'Cell AAA',
        'Duster',
        'Scrubber',
        'Surf',
        'Vim Bar',
        'File Cover',
        'File Binder',
        'Hard File Binder (Card Board)',
        'Binder Clips',
        'Toner Cartridge',
        'Key Board',
        'Water Jug',
        'Plastic Folder',
        'Sanitary Brush',
        'Broom',
        'Nepthalene BallSanitary Cubes',
        'Paper Weight',
        'Dak Pad',
        'Fluid Correction Pen',
        'Pencil',
        'Paper Clips',
        'Sharpner',
        'Punch',
        'Flag',
        'Tags',
        'Postit',
        'Sticky Notes',
        'Notesheet',
        'Marker Pen',
        'White Board Marker',
        'White board Duster',
        'Wiper',
        'Eraser',
        'Stapler',
        'Stapler Pin Box',
        'Highlighter',
        'Dustbin',
        'Garbage Bag',
        'Dust Pan',
        'Tape',
        'Scissor',
        'All Pin',
        'ACT Book',
        'Green Tea',
        'DVD, CD',
        'Pen Drive',
        'Room Freshner',
        'Call Bell',
        'Electric Kettle',
        'Plates (Full Size)',
        'Plates Quarter',
        'Spoons',
        'Drinking Glass',
        'Jug',
        'Towel',
        'Misc.'];
        $max = $this->faker->randomElement([100, 200, 300 ,400, 500]);
        $min = $this->faker->numberBetween(0,$max-80);

        return [
            'name' => $this->faker->unique()->randomElement($items),
            'in_stock' => 10,
            'max_quantity' => $max,
            'min_quantity' => $min,

        ];
    }
}
