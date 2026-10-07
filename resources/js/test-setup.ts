import { cleanup } from '@testing-library/react';
import { afterEach } from 'vite-plus/test';

// Testing Library only auto-cleans when test globals are enabled; we import everything explicitly.
afterEach(cleanup);
