<!-- changed z to display on top of handsontable -->
<template>
  <div :class="[
      zIndex,
      'absolute',
      'top-0',
      'left-0',
      'h-screen',
      'w-[calc(100%)]',
      {
        'pointer-events-none': !dialogVisible,
        'select-none': !dialogVisible
      },
    ]">
      <div class="h-screen grid grid-cols-1 mx-auto">
        <div :class="[
            'w-[calc(100%)]',
            'h-full',
            'min-w-full',
            'min-h-full',
            'absolute',
            'top-0',
            'z-[200]',
            {
              'dialogBlackFadein': dialogVisible,
              'dialogBlackFadeout': !dialogVisible,
            }
          ]"></div>
            <div :class="[
                'text-base',
                'absolute',
                'top-0',
                'z-[200]',
                'flex',
                'items-center',
                'justify-center',
                // 'bg-slate-600',
                'backdrop-blur',
                'supports-backdrop-blur:bg-white/60',
                'w-[calc(100%)]',
                'h-screen',
                {
                  'dialogFadein': dialogVisible,
                  'dialogFadeout': !dialogVisible,
                }
              ]">
                <div  :class="[
                  'rounded-xl',
                  'dark:bg-gray-300',
                  'bg-gray-300',
                  'flex',
                  'flex-col',
                  'drop-shadow',
                  'max-h-[90vh]',
                  {
                    'min-h-[200px]': (size == 'xs' && !staticHeight),
                    'min-w-[350px]': size == 'xs',
                    'max-w-[450px]': size == 'xs',
                    'h-[200px]': (size == 'xs' && staticHeight),

                    'mt-[calc(17%)]': size == 'sm',
                    'h-[calc(40%)]': (size == 'sm' && !staticHeight),
                    'min-w-[450px]': size == 'sm',
                    'max-w-[550px]': size == 'sm',
                    'h-[400px]': (size == 'sm' && staticHeight),

                    'min-h-[350px]': (size == 'ms' && !staticHeight),
                    'min-w-[650px]': size == 'ms',
                    'max-w-[750px]': size == 'ms',
                    'h-[350px]': (size == 'ms' && staticHeight),

                    'min-h-[500px]': (size == 'md' && !staticHeight),
                    'md:min-w-[750px]': size == 'md',
                    'md:max-w-[900px]': size == 'md',
                    'h-[500px]': (size == 'md' && staticHeight),

                    'min-h-[650px]': (size=='lg'&& !staticHeight),
                    'md:min-w-[850px]': size == 'lg',
                    'md:max-w-[1100px]': size == 'lg',
                    'h-[650px]': (size== 'lg' && staticHeight),

                    'min-h-[700px]': (size == 'xl' && !staticHeight),
                    'md:min-w-[950px]': size == 'xl',
                    'md:max-w-[1200px]': size == 'xl',
                    'h-[700px]': (size == 'xl' && staticHeight),

                    'w-[90vw]':size=='full',
                    'h-[85vh]':size=='full',

                    'mt-10':dialogMargin=='t-10',
                  }
                ]">
          <p class="dark:text-gray-700 w-full  text-center my-2 text-2xl text-black select-none">{{ title }}</p>
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  title: String,
  size: String,
  dialogMargin: String,
  dialogVisible: Boolean,
  zIndex: String,
  staticHeight: {
      type: Boolean,
      default: false
  },
})
</script>
