import { supabase } from './supabase.js';

async function getEquipment() {

  const { data, error } = await supabase
    .from('equipment')
    .select('*');

  if (error) {
    console.error('Error:', error.message);
    return;
  }

  console.log('Equipment:', data);
}

getEquipment();