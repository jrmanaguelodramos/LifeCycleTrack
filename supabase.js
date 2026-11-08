import { createClient } from '@supabase/supabase-js';

const supabaseUrl = 'https://bxudeybkiplnrzqqiwbn.supabase.co';

const supabaseKey = 'sb_publishable_a1iQ_6O5HaDQ-nBeuhXX6A_VgOqVxdX';

export const supabase = createClient(
  supabaseUrl,
  supabaseKey
);