import flowbitePlugin from 'flowbite/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './node_modules/flowbite/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#4F6EF7',
                primaryHover: '#3F5FE0',
                secondary: '#6C8BFF',
                accent: '#22C1C3',
                success: '#22C55E',
                warning: '#F59E0B',
                danger: '#EF4444',
                background: '#F7F9FC',
                card: '#FFFFFF',
                border: '#E6EAF2',
                textPrimary: '#1F2937',
                textSecondary: '#6B7280',
                muted: '#9CA3AF',
            },
            backgroundImage: {
                'gradient-primary': 'linear-gradient(135deg, #4F6EF7 0%, #22C1C3 100%)',
                'gradient-surface': 'linear-gradient(180deg, #FFFFFF 0%, #F7F9FC 100%)',
                'gradient-danger': 'linear-gradient(135deg, #EF4444 0%, #F59E0B 100%)',
            },
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
                heading: ['Poppins', 'sans-serif'],
            },
            boxShadow: {
                card: '0 10px 30px rgba(0,0,0,0.05)',
                hover: '0 15px 40px rgba(0,0,0,0.08)',
                modal: '0 25px 60px rgba(0,0,0,0.15)',
                glow: '0 0 20px rgba(79, 110, 247, 0.3)',
            },
            borderRadius: {
                card: '16px',
                btn: '10px',
                input: '10px',
                badge: '8px',
            },
            animation: {
                'fade-in-up': 'fadeInUp 0.5s ease-out',
                'slide-in-right': 'slideInRight 0.3s ease-out',
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
            keyframes: {
                fadeInUp: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideInRight: {
                    '0%': { transform: 'translateX(100%)' },
                    '100%': { transform: 'translateX(0)' },
                },
            },
        },
    },
    plugins: [flowbitePlugin],
};
